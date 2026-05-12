<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legal\Entity\RightOfReplyRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RightOfReplyRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RightOfReplyRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Droit de réponse')
            ->setEntityLabelInPlural('Droits de réponse')
            ->setSearchFields(['requesterName', 'requesterEmail', 'body']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('person', 'Personne concernée')->autocomplete();
        yield TextField::new('requesterName', 'Demandeur');
        yield TextField::new('requesterQuality', 'Qualité');
        yield TextField::new('requesterEmail', 'E-mail');
        yield TextField::new('requesterPhone', 'Téléphone')->hideOnIndex();
        yield TextField::new('identityPdfPath', 'Pièce jointe')->hideOnIndex();
        yield TextField::new('requestType', 'Type');
        yield TextareaField::new('body', 'Texte');
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mise à jour')->hideOnForm();
    }
}
