<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Influence\Entity\Membership;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class MembershipCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Membership::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Appartenance')
            ->setEntityLabelInPlural('Appartenances')
            ->setSearchFields([]);
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
        yield IntegerField::new('year', 'Année')->hideOnIndex();
        yield DateField::new('startDate', 'Début')->hideOnIndex();
        yield DateField::new('endDate', 'Fin')->hideOnIndex();
        yield TextField::new('roleInOrganization', 'Rôle')->hideOnIndex();
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('createdBy', 'Créé par')->hideOnIndex()->autocomplete();
    }
}
