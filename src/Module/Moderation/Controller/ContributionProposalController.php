<?php

declare(strict_types=1);

namespace App\Module\Moderation\Controller;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Moderation\Service\ChangeProposalBuilder;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contribute/propose')]
#[IsGranted('create_proposal')]
final class ContributionProposalController extends AbstractController
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly ChangeProposalBuilder $proposalBuilder,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/{entityType}/{id}/section/{sectionType}', name: 'app_contribution_propose_section', requirements: ['entityType' => 'person|organization', 'sectionType' => 'identity'], methods: ['GET', 'POST'])]
    public function proposeIdentity(
        string $entityType,
        int $id,
        string $sectionType,
        Request $request,
    ): Response {
        if ('identity' !== $sectionType) {
            throw $this->createNotFoundException();
        }

        if ('person' === $entityType) {
            $person = $this->personRepository->find($id);
            if (!$person instanceof Person || Person::STATUS_APPROVED !== $person->getStatus()) {
                throw $this->createNotFoundException();
            }

            if ($request->isMethod('POST')) {
                if (!$this->isCsrfTokenValid('propose_identity', (string) $request->request->get('_token'))) {
                    throw $this->createAccessDeniedException();
                }

                $justification = trim($request->request->getString('justification'));
                if (\strlen($justification) < 10) {
                    $this->addFlash('danger', 'La justification doit contenir au moins 10 caractères.');

                    return $this->redirect($request->getUri());
                }

                $newValues = [
                    'givenName' => trim($request->request->getString('givenName')),
                    'familyName' => trim($request->request->getString('familyName')),
                    'usageName' => $this->emptyToNull($request->request->getString('usageName')),
                ];

                $diff = $this->proposalBuilder->buildPersonIdentityDiff($person, $newValues);
                if ([] === $diff) {
                    $this->addFlash('warning', 'Aucun changement détecté.');

                    return $this->redirect($request->getUri());
                }

                $user = $this->getUser();
                if (!$user instanceof User) {
                    throw $this->createAccessDeniedException();
                }

                $proposal = $this->proposalBuilder->createProposal(
                    ChangeProposal::ENTITY_PERSON,
                    $person->getId() ?? 0,
                    $diff,
                    $justification,
                    $user,
                );

                $this->entityManager->persist($proposal);
                $this->entityManager->flush();

                $this->addFlash('success', 'Proposition enregistrée. Elle sera visible après modération.');

                return $this->redirectToRoute('app_contributor_dashboard');
            }

            return $this->render('moderation/propose_person_identity.html.twig', [
                'person' => $person,
            ]);
        }

        $organization = $this->organizationRepository->find($id);
        if (!$organization instanceof Organization || Organization::STATUS_APPROVED !== $organization->getStatus()) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('propose_identity', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $justification = trim($request->request->getString('justification'));
            if (\strlen($justification) < 10) {
                $this->addFlash('danger', 'La justification doit contenir au moins 10 caractères.');

                return $this->redirect($request->getUri());
            }

            $newValues = [
                'officialName' => trim($request->request->getString('officialName')),
                'type' => trim($request->request->getString('type')),
            ];

            $diff = $this->proposalBuilder->buildOrganizationIdentityDiff($organization, $newValues);
            if ([] === $diff) {
                $this->addFlash('warning', 'Aucun changement détecté.');

                return $this->redirect($request->getUri());
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $proposal = $this->proposalBuilder->createProposal(
                ChangeProposal::ENTITY_ORGANIZATION,
                $organization->getId() ?? 0,
                $diff,
                $justification,
                $user,
            );

            $this->entityManager->persist($proposal);
            $this->entityManager->flush();

            $this->addFlash('success', 'Proposition enregistrée.');

            return $this->redirectToRoute('app_contributor_dashboard');
        }

        return $this->render('moderation/propose_organization_identity.html.twig', [
            'organization' => $organization,
        ]);
    }

    private function emptyToNull(string $s): ?string
    {
        return '' === trim($s) ? null : $s;
    }
}
