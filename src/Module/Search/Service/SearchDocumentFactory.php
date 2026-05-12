<?php

declare(strict_types=1);

namespace App\Module\Search\Service;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Person\Entity\PersonTranslation;

final class SearchDocumentFactory
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly array $enabledLocales,
    ) {
    }

    public function shouldIndexPerson(Person $person): bool
    {
        if (Person::STATUS_APPROVED !== $person->getStatus()) {
            return false;
        }

        return null === $person->getDeletedAt();
    }

    public function shouldIndexOrganization(Organization $organization): bool
    {
        return Organization::STATUS_APPROVED === $organization->getStatus();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPersonDocument(Person $person): array
    {
        $id = $person->getId();
        if (null === $id) {
            throw new \LogicException('Person sans identifiant : impossible d’indexer.');
        }

        $nationalities = [];
        foreach ($person->getNationalities() as $country) {
            $nationalities[] = $country->getIsoCode();
        }

        $organizations = [];
        foreach ($person->getMemberships() as $membership) {
            $org = $membership->getOrganization();
            if (null !== $org && Organization::STATUS_APPROVED === $org->getStatus()) {
                $organizations[] = $org->getOfficialName();
            }
        }
        $organizations = array_values(array_unique($organizations));

        $doc = [
            'id' => (string) $id,
            'slug' => $person->getSlug(),
            'fullName' => $this->buildFullName($person),
            'usageName' => $person->getUsageName(),
            'role_categories' => $person->getRoleCategories(),
            'nationalities' => $nationalities,
            'organizations' => $organizations,
        ];

        foreach ($this->enabledLocales as $loc) {
            $parts = [];
            foreach ($person->getTranslations() as $t) {
                if ($t->getLocale() === $loc) {
                    $parts = array_merge($parts, $this->translationTexts($t));
                }
            }
            $doc['description_'.$loc] = implode("\n", array_filter($parts));
        }

        return $doc;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildOrganizationDocument(Organization $organization): array
    {
        $id = $organization->getId();
        if (null === $id) {
            throw new \LogicException('Organization sans identifiant : impossible d’indexer.');
        }

        $countries = [];
        foreach ($organization->getCountries() as $country) {
            $countries[] = $country->getIsoCode();
        }

        $doc = [
            'id' => (string) $id,
            'slug' => $organization->getSlug(),
            'officialName' => $organization->getOfficialName(),
            'type' => $organization->getType(),
            'countries' => $countries,
        ];

        foreach ($this->enabledLocales as $loc) {
            $names = [];
            $descParts = [];
            foreach ($organization->getTranslations() as $tr) {
                if ($tr->getLocale() !== $loc) {
                    continue;
                }
                $name = trim($tr->getName());
                if ('' !== $name) {
                    $names[] = $name;
                }
                $d = $tr->getDescription();
                if (null !== $d && '' !== trim($d)) {
                    $descParts[] = trim($d);
                }
            }
            $doc['translated_name_'.$loc] = implode(' ', array_values(array_unique($names)));
            $doc['org_description_'.$loc] = implode("\n", $descParts);
        }

        return $doc;
    }

    private function buildFullName(Person $person): string
    {
        return trim($person->getGivenName().' '.$person->getFamilyName());
    }

    /**
     * @return list<string>
     */
    private function translationTexts(PersonTranslation $t): array
    {
        $parts = [];
        if (null !== $t->getDescription() && '' !== trim($t->getDescription())) {
            $parts[] = trim($t->getDescription());
        }
        if (null !== $t->getBiographySummary() && '' !== trim($t->getBiographySummary())) {
            $parts[] = trim($t->getBiographySummary());
        }

        return $parts;
    }
}
