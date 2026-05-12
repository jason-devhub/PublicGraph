<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Module\User\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['email', 'username']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield EmailField::new('email', 'E-mail');
        yield TextField::new('username', 'Identifiant');
        yield ChoiceField::new('roles', 'Rôles')
            ->allowMultipleChoices()
            ->renderAsBadges()
            ->setChoices([
                'ROLE_USER' => 'ROLE_USER',
                'ROLE_MODERATOR' => 'ROLE_MODERATOR',
                'ROLE_ADMIN' => 'ROLE_ADMIN',
            ]);
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('cguAcceptedAt', 'CGU acceptées le')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'Mis à jour')->onlyOnDetail();
    }
}
