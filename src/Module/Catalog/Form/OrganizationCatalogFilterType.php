<?php

declare(strict_types=1);

namespace App\Module\Catalog\Form;

use App\Module\Catalog\Model\OrganizationCatalogFilterModel;
use App\Module\Organization\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrganizationCatalogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeChoices = [
            'Réseau d’influence' => Organization::TYPE_INFLUENCE_NETWORK,
            'Parti politique' => Organization::TYPE_POLITICAL_PARTY,
            'Entreprise' => Organization::TYPE_CORPORATION,
            'Groupe médias' => Organization::TYPE_MEDIA_GROUP,
            'Institution publique' => Organization::TYPE_GOVERNMENT_BODY,
            'Organisation internationale' => Organization::TYPE_INTERNATIONAL_BODY,
            'Think tank' => Organization::TYPE_THINK_TANK,
            'Lobby' => Organization::TYPE_LOBBY_GROUP,
            'Autre' => Organization::TYPE_OTHER,
        ];

        $builder
            ->add('types', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $typeChoices,
                'label' => false,
            ])
            ->add('countries', CountryAutocompleteField::class, [
                'required' => false,
                'multiple' => true,
                'label' => 'Pays',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganizationCatalogFilterModel::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
