<?php

declare(strict_types=1);

namespace App\Module\Catalog\Form;

use App\Module\Catalog\Model\PersonCatalogFilterModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PersonCatalogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleChoices = [
            'Politicien' => 'politician',
            'Haute fonction publique' => 'civil_servant',
            'Dirigeant économique' => 'business_leader',
            'Propriétaire médias' => 'media_owner',
            'Financier' => 'financier',
            'Lobbyiste' => 'lobbyist',
            'Autre influenceur' => 'other_influencer',
        ];

        $builder
            ->add('countries', CountryAutocompleteField::class, [
                'required' => false,
                'multiple' => true,
            ])
            ->add('roleCategories', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $roleChoices,
            ])
            ->add('organization', OrganizationAutocompleteField::class, [
                'required' => false,
            ])
            ->add('party', PartyAutocompleteField::class, [
                'required' => false,
            ])
            ->add('filterYear', CheckboxType::class, [
                'required' => false,
                'label' => 'Filtrer par années de participation (appartenances)',
            ])
            ->add('yearMin', IntegerType::class, [
                'required' => false,
                'label' => 'Depuis',
                'attr' => [
                    'min' => 1800,
                    'max' => (int) gmdate('Y'),
                    'class' => 'w-full border border-rule-medium bg-surface-primary px-3 py-2 font-sans text-sm text-text-primary',
                ],
            ])
            ->add('yearMax', IntegerType::class, [
                'required' => false,
                'label' => 'Jusqu’à',
                'attr' => [
                    'min' => 1800,
                    'max' => (int) gmdate('Y'),
                    'class' => 'w-full border border-rule-medium bg-surface-primary px-3 py-2 font-sans text-sm text-text-primary',
                ],
            ])
            ->add('aliveOnly', CheckboxType::class, [
                'required' => false,
                'label' => 'Personnes vivantes uniquement',
            ])
            ->add('activeOnly', CheckboxType::class, [
                'required' => false,
                'label' => 'Au moins un mandat ou poste en cours',
            ])
            ->add('sort', ChoiceType::class, [
                'required' => true,
                'label' => 'Classement',
                'choices' => [
                    'Alphabétique' => 'alpha',
                    'Plus récent' => 'recent',
                ],
                'attr' => [
                    'class' => 'w-full border border-rule-medium bg-surface-primary px-3 py-2.5 font-sans text-sm text-text-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonCatalogFilterModel::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
