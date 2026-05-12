<?php

declare(strict_types=1);

namespace App\Module\Moderation\Controller;

use App\Module\Legal\Entity\Report;
use App\Module\Legal\Entity\RightOfReplyRequest;
use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Moderation\Repository\ChangeProposalRepository;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
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

#[Route('/moderation')]
#[IsGranted('ROLE_MODERATOR')]
final class ModerationDashboardController extends AbstractController
{
    public function __construct(
        private readonly ChangeProposalRepository $changeProposalRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Target('change_proposal')]
        private readonly WorkflowInterface $changeProposalWorkflow,
        #[Target('person_publication')]
        private readonly WorkflowInterface $personPublicationWorkflow,
        #[Target('organization_publication')]
        private readonly WorkflowInterface $organizationPublicationWorkflow,
    ) {
    }

    #[Route('', name: 'app_moderation_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $pendingProposals = $this->changeProposalRepository->findPendingFifo();
        $pendingPersons = $this->entityManager->getRepository(Person::class)->findBy(['status' => Person::STATUS_PENDING], ['createdAt' => 'ASC']);
        $pendingOrgs = $this->entityManager->getRepository(Organization::class)->findBy(['status' => Organization::STATUS_PENDING], ['createdAt' => 'ASC']);
        $reportsCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Report::class, 'r')
            ->where('r.status = :s')->setParameter('s', Report::STATUS_RECEIVED)
            ->getQuery()->getSingleScalarResult();
        $rorCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(x.id)')
            ->from(RightOfReplyRequest::class, 'x')
            ->where('x.status = :s')->setParameter('s', RightOfReplyRequest::STATUS_PENDING)
            ->getQuery()->getSingleScalarResult();

        return $this->render('moderation/dashboard.html.twig', [
            'pendingProposals' => $pendingProposals,
            'pendingPersons' => $pendingPersons,
            'pendingOrganizations' => $pendingOrgs,
            'reportsCount' => $reportsCount,
            'rightOfReplyCount' => $rorCount,
        ]);
    }

    #[Route('/proposal/{id}', name: 'app_moderation_proposal_show', methods: ['GET'])]
    public function showProposal(ChangeProposal $proposal): Response
    {
        return $this->render('moderation/proposal_show.html.twig', [
            'proposal' => $proposal,
        ]);
    }

    #[Route('/proposal/{id}/approve', name: 'app_moderation_proposal_approve', methods: ['POST'])]
    public function approveProposal(Request $request, ChangeProposal $proposal): Response
    {
        if (!$this->isCsrfTokenValid('moderate_proposal_'.$proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->changeProposalWorkflow->apply($proposal, 'approve');
            $this->entityManager->flush();
            $this->addFlash('success', 'Proposition approuvée.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_moderation_dashboard');
    }

    #[Route('/proposal/{id}/reject', name: 'app_moderation_proposal_reject', methods: ['POST'])]
    public function rejectProposal(Request $request, ChangeProposal $proposal): Response
    {
        if (!$this->isCsrfTokenValid('moderate_proposal_'.$proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $reason = $request->request->getString('rejectionReason');
        if (\strlen(trim($reason)) < 10) {
            $this->addFlash('danger', 'Motif trop court.');

            return $this->redirectToRoute('app_moderation_proposal_show', ['id' => $proposal->getId()]);
        }

        $proposal->setRejectionReason($reason);

        try {
            $this->changeProposalWorkflow->apply($proposal, 'reject');
            $user = $this->getUser();
            if ($user instanceof User) {
                $proposal->setModeratedBy($user);
                $proposal->setModeratedAt(new \DateTimeImmutable());
            }
            $this->entityManager->flush();
            $this->addFlash('success', 'Proposition rejetée.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_moderation_dashboard');
    }

    #[Route('/person/{id}/approve', name: 'app_moderation_person_approve', methods: ['POST'])]
    public function approvePerson(Request $request, Person $person): Response
    {
        if (!$this->isCsrfTokenValid('moderate_person_'.$person->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->personPublicationWorkflow->apply($person, 'approve');
            $this->entityManager->flush();
            $this->addFlash('success', 'Fiche personne approuvée.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_moderation_dashboard');
    }

    #[Route('/organization/{id}/approve', name: 'app_moderation_organization_approve', methods: ['POST'])]
    public function approveOrganization(Request $request, Organization $organization): Response
    {
        if (!$this->isCsrfTokenValid('moderate_org_'.$organization->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->organizationPublicationWorkflow->apply($organization, 'approve');
            $this->entityManager->flush();
            $this->addFlash('success', 'Organisation approuvée.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_moderation_dashboard');
    }
}
