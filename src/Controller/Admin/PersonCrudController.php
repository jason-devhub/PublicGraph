<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Person\Entity\Person;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class PersonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Person::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Personne')
            ->setEntityLabelInPlural('Personnes')
            ->setSearchFields(['givenName', 'familyName', 'usageName']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Brouillon' => Person::STATUS_DRAFT,
                'En attente' => Person::STATUS_PENDING,
                'Approuvé' => Person::STATUS_APPROVED,
                'Rejeté' => Person::STATUS_REJECTED,
                'Archivé' => Person::STATUS_ARCHIVED,
            ]))
            ->add(ChoiceFilter::new('roleCategories')->canSelectMultiple()->setChoices([
                'Politicien' => 'politician',
                'Fonctionnaire' => 'civil_servant',
                'Chef d\'entreprise' => 'business_leader',
                'Médias' => 'media_owner',
                'Finance' => 'financier',
                'Lobbyiste' => 'lobbyist',
                'Autre influenceur' => 'other_influencer',
            ]))
            ->add(EntityFilter::new('nationalities')->canSelectMultiple()->autocomplete())
            ->add(TextFilter::new('wikidataId'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('givenName', 'Prénom');
        yield TextField::new('familyName', 'Nom');
        yield TextField::new('usageName', 'Nom d\'usage')->hideOnIndex();
        yield TextField::new('slug', 'Slug')->hideOnForm();
        yield TextField::new('status', 'Statut');
        yield AssociationField::new('nationalities', 'Pays')->autocomplete();
        yield ArrayField::new('roleCategories', 'Catégories');
        yield DateField::new('birthDate', 'Naissance')->hideOnIndex();
        yield DateField::new('deathDate', 'Décès')->hideOnIndex();
        yield TextField::new('wikidataId', 'Wikidata')->hideOnIndex();
        yield AssociationField::new('createdBy', 'Créé par')->hideOnIndex()->autocomplete();
    }
}
