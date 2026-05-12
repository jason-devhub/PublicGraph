<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

use App\Module\Organization\Entity\Organization;
use App\Module\Wikidata\Dto\OrganizationDto;

/**
 * @phpstan-import-type BindingRow from WikidataBindingValue
 */
final class WikidataOrganizationMapper
{
    /** @var array<string, string> Q31 instance of → type Organization */
    private const INSTANCE_TO_TYPE = [
        'Q43229' => Organization::TYPE_CORPORATION,
        'Q783794' => Organization::TYPE_CORPORATION,
        'Q7278' => Organization::TYPE_POLITICAL_PARTY,
        'Q891723' => Organization::TYPE_INFLUENCE_NETWORK,
        'Q46395' => Organization::TYPE_THINK_TANK,
        'Q484652' => Organization::TYPE_INTERNATIONAL_BODY,
        'Q7188' => Organization::TYPE_GOVERNMENT_BODY,
        'Q2659904' => Organization::TYPE_GOVERNMENT_BODY,
        'Q1752346' => Organization::TYPE_LOBBY_GROUP,
        'Q6881511' => Organization::TYPE_MEDIA_GROUP,
    ];

    /**
     * @param BindingRow $sparqlBinding
     */
    public function map(array $sparqlBinding): OrganizationDto
    {
        $wikidataId = WikidataBindingValue::optionalString($sparqlBinding, 'wikidataId')
            ?? $this->extractQidFromUri(WikidataBindingValue::optionalString($sparqlBinding, 'org') ?? '');
        if (null === $wikidataId || '' === $wikidataId) {
            throw new \InvalidArgumentException('Binding organisation sans QID.');
        }
        $name = WikidataBindingValue::optionalString($sparqlBinding, 'orgLabel')
            ?? WikidataBindingValue::optionalString($sparqlBinding, 'itemLabel')
            ?? 'Organisation';
        $instanceUri = WikidataBindingValue::optionalString($sparqlBinding, 'instanceOf');
        $instanceQ = $instanceUri ? $this->extractQidFromUri($instanceUri) : null;
        $type = Organization::TYPE_OTHER;
        if (null !== $instanceQ && isset(self::INSTANCE_TO_TYPE[$instanceQ])) {
            $type = self::INSTANCE_TO_TYPE[$instanceQ];
        }

        return new OrganizationDto(
            wikidataId: strtoupper($wikidataId),
            officialName: $name,
            organizationType: $type,
        );
    }

    private function extractQidFromUri(string $uri): ?string
    {
        if (preg_match('#entity/(Q\d+)$#', $uri, $m)) {
            return $m[1];
        }

        return null;
    }
}
