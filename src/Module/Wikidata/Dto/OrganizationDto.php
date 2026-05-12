<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Dto;

final class OrganizationDto
{
    public function __construct(
        public string $wikidataId,
        public string $officialName,
        public string $organizationType,
    ) {
    }
}
