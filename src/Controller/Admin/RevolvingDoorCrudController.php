<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legislation\Entity\RevolvingDoor;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RevolvingDoorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RevolvingDoor::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Porte tournante')
            ->setEntityLabelInPlural('Portes tournantes')
            ->setSearchFields(['factualNoteFr']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('person', 'Personne')->autocomplete();
        yield AssociationField::new('sourcePosition', 'Poste source')->autocomplete();
        yield AssociationField::new('targetPosition', 'Poste cible')->autocomplete();
        yield AssociationField::new('linkingAction', 'Action liée')->hideOnIndex()->autocomplete();
        yield IntegerField::new('delayDays', 'Délai (jours)')->hideOnForm();
        yield TextareaField::new('factualNoteFr', 'Note factuelle (FR)')->hideOnIndex();
        yield TextareaField::new('factualNoteEn', 'Note factuelle (EN)')->hideOnIndex();
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('createdBy', 'Créé par')->hideOnIndex()->autocomplete();
    }
}
