<?php

declare(strict_types=1);

namespace App\Module\Graph\Form;

use App\Module\Catalog\Entity\Country;
use App\Module\Catalog\Form\CountryAutocompleteField;
use App\Module\Catalog\Form\OrganizationAutocompleteField;
use App\Module\Graph\Model\GraphFilterModel;
use App\Module\Organization\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GraphFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputNum = 'pg-input mt-1 min-h-11 w-full px-2 py-1 font-mono text-xs';
        $selectCls = 'pg-input mt-1 min-h-11 w-full px-2 py-1.5 text-xs';

        $roleChoices = [
            'global_graph.role_categories.politician' => 'politician',
            'global_graph.role_categories.civil_servant' => 'civil_servant',
            'global_graph.role_categories.business_leader' => 'business_leader',
            'global_graph.role_categories.media_owner' => 'media_owner',
            'global_graph.role_categories.financier' => 'financier',
            'global_graph.role_categories.lobbyist' => 'lobbyist',
            'global_graph.role_categories.other_influencer' => 'other_influencer',
        ];

        $builder
            ->add('organization', OrganizationAutocompleteField::class, [
                'required' => false,
                'label' => 'global_graph.filter_org',
                'translation_domain' => 'messages',
                'choice_value' => static fn (?Organization $o): string => null !== $o ? $o->getSlug() : '',
            ])
            ->add('countries', CountryAutocompleteField::class, [
                'required' => false,
                'label' => 'global_graph.filter_countries',
                'translation_domain' => 'messages',
                'multiple' => true,
                'choice_value' => static fn (?Country $c): string => null !== $c ? $c->getIsoCode() : '',
            ])
            ->add('categories', ChoiceType::class, [
                'required' => false,
                'label' => 'global_graph.filter_categories',
                'translation_domain' => 'messages',
                'choices' => $roleChoices,
                'choice_translation_domain' => 'messages',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('yearMin', IntegerType::class, [
                'required' => false,
                'label' => 'global_graph.filter_year_min',
                'translation_domain' => 'messages',
                'attr' => [
                    'class' => $inputNum,
                ],
            ])
            ->add('yearMax', IntegerType::class, [
                'required' => false,
                'label' => 'global_graph.filter_year_max',
                'translation_domain' => 'messages',
                'attr' => [
                    'class' => $inputNum,
                ],
            ])
            ->add('colorMode', ChoiceType::class, [
                'required' => true,
                'label' => 'global_graph.filter_color',
                'translation_domain' => 'messages',
                'choices' => [
                    'global_graph.color_by_category' => 'category',
                    'global_graph.color_by_country' => 'country',
                ],
                'choice_translation_domain' => 'messages',
                'attr' => [
                    'class' => $selectCls,
                ],
            ])
            ->add('maxNodes', ChoiceType::class, [
                'required' => true,
                'label' => 'global_graph.filter_max_nodes',
                'translation_domain' => 'messages',
                'choices' => [
                    '25' => 25,
                    '50' => 50,
                    '100' => 100,
                    '200' => 200,
                ],
                'choice_translation_domain' => false,
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'mt-2 flex flex-wrap gap-x-4 gap-y-2 font-mono text-xs',
                ],
                'choice_attr' => static fn (): array => [
                    'class' => 'size-4 shrink-0 accent-accent',
                ],
                'row_attr' => [
                    'class' => 'min-w-0 flex-1',
                ],
            ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GraphFilterModel::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
