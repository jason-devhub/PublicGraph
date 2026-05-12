<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Dto;

/**
 * Représentation intermédiaire d’une personne Wikidata avant persistance.
 */
final class PersonDto
{
    public function __construct(
        public string $wikidataId,
        public string $givenName,
        public string $familyName,
        public ?string $fullLabel = null,
        public ?\DateTimeImmutable $birthDate = null,
        public ?\DateTimeImmutable $deathDate = null,
        public ?string $gender = null,
        public ?string $photoUrl = null,
        public array $nationalityQids = [],
        public array $roleCategories = [],
        public array $partyMemberships = [],
        public array $organizationMemberships = [],
        public array $positionsHeld = [],
    ) {
    }
}
