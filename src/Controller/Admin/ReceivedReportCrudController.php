<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legal\Entity\Report;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: 'moderation/reports', name: 'moderation_reports')]
final class ReceivedReportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Report::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Signalements reçus')
            ->setEntityLabelInSingular('Signalement')
            ->setPageTitle('index', 'Signalements non traités (statut : reçu)');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.status = :received')->setParameter('received', Report::STATUS_RECEIVED);

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('entityType', 'Type d\'entité');
        yield TextField::new('entityId', 'ID');
        yield TextField::new('reason', 'Motif');
        yield TextareaField::new('description', 'Description');
        yield TextField::new('contactEmail', 'E-mail')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Reçu le');
    }
}
