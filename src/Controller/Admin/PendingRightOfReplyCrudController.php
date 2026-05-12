<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Legal\Entity\RightOfReplyRequest;
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

#[AdminRoute(path: 'moderation/right-of-reply', name: 'moderation_right_of_reply')]
final class PendingRightOfReplyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RightOfReplyRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Droits de réponse à traiter')
            ->setEntityLabelInSingular('Demande')
            ->setPageTitle('index', 'Droits de réponse (en attente ou en cours)');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.status IN (:st)')
            ->setParameter('st', [RightOfReplyRequest::STATUS_PENDING, RightOfReplyRequest::STATUS_UNDER_REVIEW]);

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield AssociationField::new('person', 'Personne')->autocomplete();
        yield TextField::new('requesterName', 'Demandeur');
        yield TextField::new('requesterEmail', 'E-mail');
        yield TextField::new('requestType', 'Type');
        yield TextField::new('status', 'Statut');
        yield TextareaField::new('body', 'Texte')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le');
    }
}
