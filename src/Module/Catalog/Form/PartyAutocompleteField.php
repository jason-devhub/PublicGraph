<?php

declare(strict_types=1);

namespace App\Module\Catalog\Form;

use App\Module\Organization\Entity\Organization;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
final class PartyAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Organization::class,
            'choice_label' => static fn (Organization $o): string => $o->getOfficialName(),
            'placeholder' => 'Parti politique',
            'multiple' => false,
            'filter_query' => static function (QueryBuilder $qb, string $query, EntityRepository $repository): void {
                $qb->andWhere('entity.status = :partyAutocompleteStatus')
                    ->setParameter('partyAutocompleteStatus', 'approved')
                    ->andWhere('entity.type = :partyType')
                    ->setParameter('partyType', Organization::TYPE_POLITICAL_PARTY);
                if ('' !== $query) {
                    $qb->andWhere('entity.officialName LIKE :partyQ')->setParameter('partyQ', '%'.$query.'%');
                }
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
