<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Influence\Entity\Position;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class PositionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Position::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Poste')
            ->setEntityLabelInPlural('Postes')
            ->setSearchFields(['titleFr', 'titleEn']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('person', 'Personne')->autocomplete();
        yield AssociationField::new('organization', 'Organisation')->autocomplete();
        yield TextField::new('titleFr', 'Intitulé (FR)');
        yield TextField::new('titleEn', 'Intitulé (EN)')->hideOnIndex();
        yield TextField::new('nature', 'Nature');
        yield DateField::new('startDate', 'Début');
        yield DateField::new('endDate', 'Fin')->hideOnIndex();
        yield AssociationField::new('country', 'Pays')->hideOnIndex()->autocomplete();
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('createdBy', 'Créé par')->hideOnIndex()->autocomplete();
    }
}
