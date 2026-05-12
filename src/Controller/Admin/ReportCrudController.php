<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legal\Entity\Report;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ReportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Report::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Signalement')
            ->setEntityLabelInPlural('Signalements')
            ->setSearchFields(['description', 'contactEmail']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('entityType', 'Type d\'entité');
        yield TextField::new('entityId', 'ID');
        yield TextField::new('reason', 'Motif');
        yield TextareaField::new('description', 'Description');
        yield TextField::new('contactEmail', 'E-mail contact')->hideOnIndex();
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('createdAt', 'Reçu le');
        yield DateTimeField::new('processedAt', 'Traité le')->hideOnIndex();
    }
}
