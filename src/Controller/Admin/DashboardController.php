<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legal\Entity\Report;
use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Person\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function index(): Response
    {
        $personByStatus = $this->personRepository->countByStatus();

        $pendingProposals = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(cp.id)')
            ->from(ChangeProposal::class, 'cp')
            ->where('cp.status = :p')
            ->setParameter('p', ChangeProposal::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

        $reportsReceived = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Report::class, 'r')
            ->where('r.status = :s')
            ->setParameter('s', Report::STATUS_RECEIVED)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'person_by_status' => $personByStatus,
            'pending_proposals_count' => $pendingProposals,
            'reports_received_count' => $reportsReceived,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('PublicGraph — Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-house');

        yield MenuItem::section('Données');
        yield MenuItem::linkTo(PersonCrudController::class, 'Personnes', 'fa fa-user');
        yield MenuItem::linkTo(OrganizationCrudController::class, 'Organisations', 'fa fa-building');
        yield MenuItem::linkTo(PartyCrudController::class, 'Partis', 'fa fa-flag');
        yield MenuItem::linkTo(MembershipCrudController::class, 'Appartenances', 'fa fa-link');
        yield MenuItem::linkTo(PositionCrudController::class, 'Postes / mandats', 'fa fa-briefcase');
        yield MenuItem::linkTo(LegislativeActionCrudController::class, 'Actions législatives', 'fa fa-gavel');
        yield MenuItem::linkTo(RevolvingDoorCrudController::class, 'Portes tournantes', 'fa fa-right-left');
        yield MenuItem::linkTo(SourceCrudController::class, 'Sources', 'fa fa-bookmark');

        yield MenuItem::section('Modération — files');
        yield MenuItem::linkTo(PendingChangeProposalCrudController::class, 'Propositions en attente', 'fa fa-clock');
        yield MenuItem::linkTo(ReceivedReportCrudController::class, 'Signalements reçus', 'fa fa-flag');
        yield MenuItem::linkTo(PendingRightOfReplyCrudController::class, 'Droits de réponse', 'fa fa-message');

        yield MenuItem::section('Modération — tout voir');
        yield MenuItem::linkTo(ChangeProposalCrudController::class, 'Propositions (toutes)', 'fa fa-list');
        yield MenuItem::linkTo(ReportCrudController::class, 'Signalements (tous)', 'fa fa-list');
        yield MenuItem::linkTo(RightOfReplyRequestCrudController::class, 'Droits de réponse (tous)', 'fa fa-list');

        yield MenuItem::section('Administration');
        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        }
    }
}
