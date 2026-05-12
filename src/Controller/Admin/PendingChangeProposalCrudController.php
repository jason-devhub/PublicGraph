<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;
use Symfony\Component\Workflow\WorkflowInterface;

#[AdminRoute(path: 'moderation/proposals', name: 'moderation_proposals')]
final class PendingChangeProposalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChangeProposal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Propositions en attente')
            ->setEntityLabelInSingular('Proposition')
            ->setPageTitle('index', 'Propositions en attente de modération');
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', 'Approuver', 'fa fa-check')
            ->linkToCrudAction('approve')
            ->renderAsButton()
            ->setCssClass('btn btn-success btn-sm');

        $reject = Action::new('reject', 'Rejeter', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->renderAsButton()
            ->setCssClass('btn btn-danger btn-sm');

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_DETAIL, $reject);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.status = :pending')->setParameter('pending', ChangeProposal::STATUS_PENDING);

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('entityType', 'Type d\'entité');
        yield TextField::new('entityId', 'ID entité');
        yield TextareaField::new('justification', 'Justification');
        yield AssociationField::new('submittedBy', 'Auteur')->autocomplete();
        yield DateTimeField::new('createdAt', 'Créée le');
    }

    #[AdminRoute(path: '/{entityId}/approve', options: ['methods' => ['POST']])]
    public function approve(
        ChangeProposal $changeProposal,
        AdminUrlGeneratorInterface $adminUrlGenerator,
        #[Target('change_proposal')]
        WorkflowInterface $changeProposalWorkflow,
    ): Response {
        if (ChangeProposal::STATUS_PENDING !== $changeProposal->getStatus()) {
            $this->addFlash('warning', 'Cette proposition n\'est plus en attente.');

            return $this->redirect($adminUrlGenerator->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $changeProposalWorkflow->apply($changeProposal, 'approve');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', 'Impossible d\'approuver : '.$e->getMessage());

            return $this->redirect($adminUrlGenerator->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::DETAIL)->setEntityId((string) $changeProposal->getId())->generateUrl());
        }

        $this->addFlash('success', 'Proposition approuvée.');

        return $this->redirect($adminUrlGenerator->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute(path: '/{entityId}/reject', options: ['methods' => ['GET', 'POST']])]
    public function reject(
        Request $request,
        ChangeProposal $changeProposal,
        EntityManagerInterface $entityManager,
        AdminUrlGeneratorInterface $adminUrlGenerator,
        #[Target('change_proposal')]
        WorkflowInterface $changeProposalWorkflow,
    ): Response {
        if (ChangeProposal::STATUS_PENDING !== $changeProposal->getStatus()) {
            $this->addFlash('warning', 'Cette proposition n\'est plus en attente.');

            return $this->redirect($adminUrlGenerator->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        if ($request->isMethod('POST')) {
            $tokenId = 'reject_proposal_'.$changeProposal->getId();
            if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $reason = $request->request->getString('rejectionReason');
            if (\strlen(trim($reason)) < 10) {
                $this->addFlash('danger', 'Le motif de rejet doit contenir au moins 10 caractères.');

                return $this->redirect($request->getUri());
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $changeProposal->setRejectionReason($reason);

            try {
                $changeProposalWorkflow->apply($changeProposal, 'reject');
            } catch (WorkflowLogicException $e) {
                $this->addFlash('danger', 'Impossible de rejeter : '.$e->getMessage());

                return $this->redirect($request->getUri());
            }

            $changeProposal->setModeratedBy($user);
            $changeProposal->setModeratedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Proposition rejetée.');

            return $this->redirect($adminUrlGenerator->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        return $this->render('admin/moderation/reject_change_proposal.html.twig', [
            'proposal' => $changeProposal,
            'reject_submit_url' => $adminUrlGenerator->setDashboard(DashboardController::class)->setController(self::class)->setAction('reject')->setEntityId((string) $changeProposal->getId())->generateUrl(),
        ]);
    }
}
