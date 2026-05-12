<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\Organization\Entity\Party;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class PartyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Party::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Parti')
            ->setEntityLabelInPlural('Partis')
            ->setSearchFields(['europeanFamily', 'internationalFamily']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('organization', 'Organisation')->autocomplete();
        yield TextField::new('europeanFamily', 'Famille européenne')->hideOnIndex();
        yield TextField::new('internationalFamily', 'Famille internationale')->hideOnIndex();
        yield TextField::new('colorHex', 'Couleur (#RRGGBB)');
    }
}
