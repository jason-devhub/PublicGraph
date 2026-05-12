<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Moderation\Repository\ChangeProposalRepository;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
final class ContributorDashboardController extends AbstractController
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly ChangeProposalRepository $changeProposalRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Target('change_proposal')]
        private readonly WorkflowInterface $changeProposalWorkflow,
    ) {
    }

    #[Route('', name: 'app_contributor_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAppUser();

        $persons = $this->personRepository->findBy(['createdBy' => $user], ['updatedAt' => 'DESC']);
        $proposals = $this->changeProposalRepository->findBy(['submittedBy' => $user], ['createdAt' => 'DESC']);

        $personGroups = $this->groupByStatus($persons, static fn (Person $p) => $p->getStatus());
        $proposalGroups = $this->groupByStatus($proposals, static fn (ChangeProposal $c) => $c->getStatus());

        $approvedCount = \count($proposalGroups[ChangeProposal::STATUS_APPROVED] ?? []);
        $totalFinished = $approvedCount + \count($proposalGroups[ChangeProposal::STATUS_REJECTED] ?? []);
        $acceptRate = $totalFinished > 0 ? round(100 * $approvedCount / $totalFinished) : null;

        return $this->render('user/contributor_dashboard.html.twig', [
            'personGroups' => $personGroups,
            'proposalGroups' => $proposalGroups,
            'stats' => [
                'contributions' => \count($proposals),
                'approved' => $approvedCount,
                'accept_rate' => $acceptRate,
            ],
        ]);
    }

    #[Route('/proposal/{id}/withdraw', name: 'app_contributor_proposal_withdraw', methods: ['POST'])]
    public function withdrawProposal(Request $request, ChangeProposal $proposal): Response
    {
        $user = $this->getAppUser();
        if (!$this->isCsrfTokenValid('withdraw_proposal_'.$proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($proposal->getSubmittedBy()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (ChangeProposal::STATUS_PENDING !== $proposal->getStatus()) {
            $this->addFlash('warning', 'Cette proposition n\'est plus modifiable.');

            return $this->redirectToRoute('app_contributor_dashboard');
        }

        try {
            $this->changeProposalWorkflow->apply($proposal, 'withdraw');
            $this->entityManager->flush();
            $this->addFlash('success', 'Proposition retirée.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_contributor_dashboard');
    }

    private function getAppUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    /**
     * @template T of object
     *
     * @param iterable<int, T>    $items
     * @param callable(T): string $statusGetter
     *
     * @return array<string, list<T>>
     */
    private function groupByStatus(iterable $items, callable $statusGetter): array
    {
        $out = [];
        foreach ($items as $item) {
            $s = $statusGetter($item);
            $out[$s] ??= [];
            $out[$s][] = $item;
        }

        return $out;
    }
}
