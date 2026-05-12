<?php

declare(strict_types=1);

namespace App\Module\Moderation\Service;

use App\Module\Catalog\Entity\Country;
use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\OrganizationTranslation;
use App\Module\Person\Entity\Person;
use App\Module\Person\Entity\PersonTranslation;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique le diff JSON d'une ChangeProposal sur l'entité cible (champs supportés).
 */
final class ChangeProposalDiffApplier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}> $diff
     */
    public function apply(ChangeProposal $proposal, array $diff): void
    {
        if ([] === $diff) {
            return;
        }

        $type = $proposal->getEntityType();
        $id = $proposal->getEntityId();

        if (ChangeProposal::ENTITY_PERSON === $type) {
            $person = $this->entityManager->find(Person::class, $id);
            if (!$person instanceof Person) {
                throw new \InvalidArgumentException('Person introuvable pour la proposition.');
            }
            $this->applyToPerson($person, $diff);

            return;
        }

        if (ChangeProposal::ENTITY_ORGANIZATION === $type) {
            $org = $this->entityManager->find(Organization::class, $id);
            if (!$org instanceof Organization) {
                throw new \InvalidArgumentException('Organization introuvable pour la proposition.');
            }
            $this->applyToOrganization($org, $diff);

            return;
        }

        throw new \InvalidArgumentException('Type d\'entité non géré: '.$type);
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}> $diff
     */
    private function applyToPerson(Person $person, array $diff): void
    {
        foreach ($diff as $field => $change) {
            $new = $change['new'] ?? null;
            if ('nationalityIsoCodes' === $field) {
                $this->syncPersonNationalities($person, $new);
            } elseif ('translationFrDescription' === $field) {
                $this->setPersonTranslationField($person, 'fr', 'description', $new);
            } elseif ('translationEnDescription' === $field) {
                $this->setPersonTranslationField($person, 'en', 'description', $new);
            } elseif ('givenName' === $field) {
                $person->setGivenName((string) $new);
            } elseif ('familyName' === $field) {
                $person->setFamilyName((string) $new);
            } elseif ('usageName' === $field) {
                $person->setUsageName(null !== $new && '' !== (string) $new ? (string) $new : null);
            } elseif ('birthDate' === $field) {
                $person->setBirthDate($this->parseDate($new));
            } elseif ('deathDate' === $field) {
                $person->setDeathDate($this->parseDate($new));
            } elseif ('gender' === $field) {
                $person->setGender(null !== $new && '' !== (string) $new ? (string) $new : null);
            } elseif ('roleCategories' === $field) {
                $list = \is_array($new) ? array_values(array_map(static fn (mixed $x) => (string) $x, $new)) : [];
                $person->setRoleCategories($list);
            } elseif ('photoUrl' === $field) {
                $person->setPhotoUrl(null !== $new && '' !== (string) $new ? (string) $new : null);
            } elseif ('wikidataId' === $field) {
                $person->setWikidataId(null !== $new && '' !== (string) $new ? (string) $new : null);
            }
        }
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}> $diff
     */
    private function applyToOrganization(Organization $org, array $diff): void
    {
        foreach ($diff as $field => $change) {
            $new = $change['new'] ?? null;
            if ('countryIsoCodes' === $field) {
                $this->syncOrganizationCountries($org, $new);
            } elseif ('translationFrName' === $field) {
                $this->setOrganizationTranslationName($org, 'fr', $new);
            } elseif ('officialName' === $field) {
                $org->setOfficialName((string) $new);
            } elseif ('type' === $field) {
                $org->setType((string) $new);
            } elseif ('websiteUrl' === $field) {
                $org->setWebsiteUrl(null !== $new && '' !== (string) $new ? (string) $new : null);
            } elseif ('foundedYear' === $field) {
                $org->setFoundedYear($this->parseSmallInt($new));
            } elseif ('dissolvedYear' === $field) {
                $org->setDissolvedYear($this->parseSmallInt($new));
            } elseif ('wikidataId' === $field) {
                $org->setWikidataId(null !== $new && '' !== (string) $new ? (string) $new : null);
            }
        }
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        return new \DateTimeImmutable((string) $value);
    }

    private function parseSmallInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (int) $value;
    }

    private function syncPersonNationalities(Person $person, mixed $new): void
    {
        if (!\is_array($new)) {
            return;
        }

        foreach ($person->getNationalities()->toArray() as $c) {
            $person->getNationalities()->removeElement($c);
        }

        foreach ($new as $iso) {
            $code = strtoupper((string) $iso);
            $country = $this->entityManager->find(Country::class, $code);
            if ($country instanceof Country) {
                $person->addNationality($country);
            }
        }
    }

    private function setPersonTranslationField(Person $person, string $locale, string $aspect, mixed $newText): void
    {
        $text = null !== $newText && '' !== trim((string) $newText) ? (string) $newText : null;
        foreach ($person->getTranslations() as $tr) {
            if ($tr->getLocale() === $locale) {
                if ('description' === $aspect) {
                    $tr->setDescription($text);
                }

                return;
            }
        }

        if (null === $text) {
            return;
        }

        $tr = new PersonTranslation();
        $tr->setLocale($locale);
        $tr->setPerson($person);
        if ('description' === $aspect) {
            $tr->setDescription($text);
        }
        $person->addTranslation($tr);
    }

    private function syncOrganizationCountries(Organization $org, mixed $new): void
    {
        if (!\is_array($new)) {
            return;
        }

        foreach ($org->getCountries()->toArray() as $c) {
            $org->getCountries()->removeElement($c);
        }

        foreach ($new as $iso) {
            $code = strtoupper((string) $iso);
            $country = $this->entityManager->find(Country::class, $code);
            if ($country instanceof Country) {
                $org->addCountry($country);
            }
        }
    }

    private function setOrganizationTranslationName(Organization $org, string $locale, mixed $newName): void
    {
        $name = null !== $newName && '' !== trim((string) $newName) ? (string) $newName : null;
        foreach ($org->getTranslations() as $tr) {
            if ($tr->getLocale() === $locale) {
                $tr->setName($name ?? '');

                return;
            }
        }

        if (null === $name) {
            return;
        }

        $tr = new OrganizationTranslation();
        $tr->setLocale($locale);
        $tr->setOrganization($org);
        $tr->setName($name);
        $org->addTranslation($tr);
    }
}
