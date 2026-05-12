<?php

declare(strict_types=1);

namespace App\Module\Catalog\Form;

use App\Module\Catalog\Entity\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
final class CountryAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Country::class,
            'choice_label' => static fn (Country $c): string => $c->getNameFr(),
            'searchable_fields' => ['nameFr', 'nameEn', 'isoCode'],
            'placeholder' => 'Pays',
            'multiple' => false,
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
