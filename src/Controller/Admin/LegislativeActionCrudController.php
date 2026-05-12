<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legislation\Entity\LegislativeAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class LegislativeActionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LegislativeAction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Action législative')
            ->setEntityLabelInPlural('Actions législatives')
            ->setSearchFields(['titleFr', 'titleEn']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('author', 'Auteur')->autocomplete();
        yield AssociationField::new('contextualPosition', 'Contexte')->hideOnIndex()->autocomplete();
        yield TextField::new('type', 'Type');
        yield DateField::new('actionDate', 'Date');
        yield TextField::new('titleFr', 'Titre (FR)');
        yield TextField::new('titleEn', 'Titre (EN)')->hideOnIndex();
        yield TextareaField::new('descriptionFr', 'Description (FR)');
        yield TextareaField::new('descriptionEn', 'Description (EN)')->hideOnIndex();
        yield AssociationField::new('beneficiaryOrganizations', 'Bénéficiaires')->autocomplete();
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('createdBy', 'Créé par')->hideOnIndex()->autocomplete();
    }
}
