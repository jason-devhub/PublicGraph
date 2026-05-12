<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Moderation\Entity\ChangeProposal;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ChangeProposalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChangeProposal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Proposition')
            ->setEntityLabelInPlural('Propositions')
            ->setSearchFields(['entityType', 'justification']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('entityType', 'Type d\'entité');
        yield TextField::new('entityId', 'ID entité');
        yield TextareaField::new('diff', 'Diff JSON')->onlyOnDetail()->formatValue(function (mixed $value): string {
            if (!\is_array($value)) {
                return '';
            }

            try {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return '';
            }
        });
        yield TextareaField::new('justification', 'Justification');
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('submittedBy', 'Soumis par')->autocomplete();
        yield AssociationField::new('moderatedBy', 'Modéré par')->autocomplete();
        yield DateTimeField::new('moderatedAt', 'Modéré le')->hideOnIndex();
        yield TextareaField::new('rejectionReason', 'Motif de rejet')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mise à jour')->hideOnForm();
    }
}
