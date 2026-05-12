<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Source\Entity\Source;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

final class SourceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Source::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Source')
            ->setEntityLabelInPlural('Sources')
            ->setSearchFields(['url', 'title', 'domain']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield UrlField::new('url', 'URL');
        yield TextField::new('title', 'Titre')->hideOnIndex();
        yield TextField::new('type', 'Type');
        yield TextField::new('domain', 'Domaine')->hideOnForm();
        yield DateField::new('accessedAt', 'Consultée le');
        yield TextField::new('checkStatus', 'Vérification');
        yield DateTimeField::new('lastCheckedAt', 'Dernière vérif.')->hideOnIndex();
        yield UrlField::new('waybackUrl', 'Wayback')->hideOnIndex();
    }
}
