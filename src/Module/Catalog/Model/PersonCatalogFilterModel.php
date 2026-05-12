<?php

declare(strict_types=1);

namespace App\Module\Catalog\Model;

use App\Module\Organization\Entity\Organization;

/** Données du formulaire de filtres catalogue personnes (T3.2). */
final class PersonCatalogFilterModel
{
    /** @var list<string> codes ISO pays */
    public array $countries = [];

    /** @var list<string> valeurs role_categories */
    public array $roleCategories = [];

    public ?Organization $organization = null;

    public ?Organization $party = null;

    public bool $filterYear = false;

    public ?int $yearMin = null;

    public ?int $yearMax = null;

    public bool $aliveOnly = false;

    public bool $activeOnly = false;

    /** alpha | recent */
    public string $sort = 'alpha';
}
