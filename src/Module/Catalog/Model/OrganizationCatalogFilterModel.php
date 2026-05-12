<?php

declare(strict_types=1);

namespace App\Module\Catalog\Model;

use App\Module\Catalog\Entity\Country;

final class OrganizationCatalogFilterModel
{
    /** @var list<string>|null si null ou vide : tous les types */
    public ?array $types = null;

    /** @var list<Country> */
    public array $countries = [];
}
