<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Organization\Entity\Organization;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class OrganizationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Organization::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Organisation')
            ->setEntityLabelInPlural('Organisations')
            ->setSearchFields(['officialName', 'slug']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type')->setChoices([
                'Réseau d\'influence' => Organization::TYPE_INFLUENCE_NETWORK,
                'Parti politique' => Organization::TYPE_POLITICAL_PARTY,
                'Corporation' => Organization::TYPE_CORPORATION,
                'Groupe médias' => Organization::TYPE_MEDIA_GROUP,
                'État / administration' => Organization::TYPE_GOVERNMENT_BODY,
                'Organisation internationale' => Organization::TYPE_INTERNATIONAL_BODY,
                'Think tank' => Organization::TYPE_THINK_TANK,
                'Lobby' => Organization::TYPE_LOBBY_GROUP,
                'Autre' => Organization::TYPE_OTHER,
            ]))
            ->add(ChoiceFilter::new('status')->setChoices([
                'Brouillon' => 'draft',
                'En attente' => 'pending',
                'Approuvé' => 'approved',
                'Rejeté' => 'rejected',
                'Archivé' => 'archived',
            ]))
            ->add(EntityFilter::new('countries')->autocomplete())
            ->add(TextFilter::new('wikidataId'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('officialName', 'Nom officiel');
        yield TextField::new('slug', 'Slug')->hideOnForm();
        yield TextField::new('type', 'Type');
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('countries', 'Pays')->autocomplete();
        yield UrlField::new('websiteUrl', 'Site web')->hideOnIndex();
        yield IntegerField::new('foundedYear', 'Année fondation')->hideOnIndex();
        yield IntegerField::new('dissolvedYear', 'Année dissolution')->hideOnIndex();
        yield TextField::new('wikidataId', 'Wikidata')->hideOnIndex();
    }
}
