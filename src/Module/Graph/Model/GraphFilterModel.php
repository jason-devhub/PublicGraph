<?php

declare(strict_types=1);

namespace App\Module\Graph\Model;

use App\Module\Catalog\Entity\Country;
use App\Module\Organization\Entity\Organization;
use Doctrine\Persistence\ObjectRepository;

/** Données du formulaire de filtres page graphe global (GET). */
final class GraphFilterModel
{
    public ?Organization $organization = null;

    /** @var list<Country> */
    public array $countries = [];

    /** @var list<string> */
    public array $categories = [];

    public ?int $yearMin = null;

    public ?int $yearMax = null;

    public int $maxNodes = 100;

    public string $colorMode = 'category';

    /**
     * @param ObjectRepository<Country> $countryRepository
     */
    public static function fromGraphQueryParams(
        GraphQueryParams $params,
        ?Organization $organization,
        ObjectRepository $countryRepository,
    ): self {
        $m = new self();
        $m->organization = $organization;
        foreach ($params->countryIsoCodes as $code) {
            $c = $countryRepository->find(strtoupper($code));
            if ($c instanceof Country) {
                $m->countries[] = $c;
            }
        }
        $m->categories = $params->roleCategories;
        $m->yearMin = $params->yearMin;
        $m->yearMax = $params->yearMax;
        $m->maxNodes = $params->maxNodes;
        $m->colorMode = $params->colorMode;

        return $m;
    }
}
