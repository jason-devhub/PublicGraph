<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

use App\Module\Wikidata\Dto\PersonDto;
use App\Module\Wikidata\Util\WikimediaImageUrl;

/**
 * @phpstan-import-type BindingRow from WikidataBindingValue
 */
final class WikidataPersonMapper
{
    /** @var array<string, string> QID occupation → roleCategories locale */
    private const OCCUPATION_TO_ROLE = [
        'Q82955' => 'politician',
        'Q193391' => 'civil_servant',
        'Q486839' => 'civil_servant',
        'Q2285706' => 'civil_servant',
        'Q43845' => 'business_leader',
        'Q15978631' => 'business_leader',
        'Q3282637' => 'lobbyist',
        'Q27038949' => 'lobbyist',
        'Q11063' => 'media_owner',
        'Q2405480' => 'media_owner',
        'Q1416902' => 'financier',
    ];

    /**
     * @param BindingRow $sparqlBinding
     */
    public function map(array $sparqlBinding): PersonDto
    {
        $wikidataId = WikidataBindingValue::optionalString($sparqlBinding, 'wikidataId')
            ?? $this->extractQidFromUri(WikidataBindingValue::optionalString($sparqlBinding, 'person') ?? '');
        if (null === $wikidataId || '' === $wikidataId) {
            throw new \InvalidArgumentException('Binding sans wikidataId.');
        }

        $label = WikidataBindingValue::optionalString($sparqlBinding, 'personLabel')
            ?? WikidataBindingValue::optionalString($sparqlBinding, 'itemLabel');
        if (null === $label || '' === $label) {
            throw new \InvalidArgumentException('Binding sans libellé personne.');
        }

        [$given, $family] = $this->splitName($label);

        $genderUri = WikidataBindingValue::optionalString($sparqlBinding, 'gender');
        $gender = null;
        if (null !== $genderUri) {
            $gq = $this->extractQidFromUri($genderUri);
            $gender = match ($gq) {
                'Q6581097' => 'male',
                'Q6581072' => 'female',
                default => null,
            };
        }

        $photo = null;
        $imageFile = WikidataBindingValue::optionalString($sparqlBinding, 'image');
        if (null !== $imageFile) {
            $photo = WikimediaImageUrl::buildThumbnail($imageFile, 250);
        }

        $nationalityQids = $this->splitPipe(WikidataBindingValue::optionalString($sparqlBinding, 'nationalityQids'));
        $occupationQids = $this->splitPipe(WikidataBindingValue::optionalString($sparqlBinding, 'occupationQids'));
        $roleCategories = $this->mapOccupationsToRoles($occupationQids);
        if ([] === $roleCategories) {
            $roleCategories = ['other_influencer'];
        }

        return new PersonDto(
            wikidataId: $wikidataId,
            givenName: $given,
            familyName: $family,
            fullLabel: $label,
            birthDate: WikidataBindingValue::optionalDateImmutable($sparqlBinding, 'birthDate'),
            deathDate: WikidataBindingValue::optionalDateImmutable($sparqlBinding, 'deathDate'),
            gender: $gender,
            photoUrl: $photo,
            nationalityQids: $nationalityQids,
            roleCategories: array_values(array_unique($roleCategories)),
            partyMemberships: $this->parseQidLabelPairs(WikidataBindingValue::optionalString($sparqlBinding, 'partyPairs')),
            organizationMemberships: $this->parseQidLabelPairs(WikidataBindingValue::optionalString($sparqlBinding, 'orgPairs')),
            positionsHeld: $this->parsePositionPairs(WikidataBindingValue::optionalString($sparqlBinding, 'positionPairs')),
        );
    }

    private function extractQidFromUri(string $uri): ?string
    {
        if (preg_match('#entity/(Q\d+)$#', $uri, $m)) {
            return $m[1];
        }

        return null;
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $label): array
    {
        $label = trim($label);
        if (str_contains($label, ',')) {
            $parts = array_map('trim', explode(',', $label, 2));

            return [$parts[1] ?? '', $parts[0] ?? $label];
        }
        $parts = preg_split('/\s+/u', $label) ?: [];
        if (\count($parts) < 2) {
            return [$label, ''];
        }
        $family = array_pop($parts);
        $given = implode(' ', $parts);

        return [$given, $family];
    }

    /** @return list<string> */
    private function splitPipe(?string $s): array
    {
        if (null === $s || '' === $s) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode('|', $s))));
    }

    /** @param list<string> $occupationQids */
    /** @return list<string> */
    private function mapOccupationsToRoles(array $occupationQids): array
    {
        $roles = [];
        foreach ($occupationQids as $q) {
            if (!preg_match('/(Q\d+)/i', $q, $m)) {
                continue;
            }
            $qNorm = strtoupper($m[1]);
            if (isset(self::OCCUPATION_TO_ROLE[$qNorm])) {
                $roles[] = self::OCCUPATION_TO_ROLE[$qNorm];
            }
        }

        return $roles;
    }

    /** @return list<array{qid: string, label: string}> */
    private function parseQidLabelPairs(?string $raw): array
    {
        if (null === $raw || '' === $raw) {
            return [];
        }
        $out = [];
        foreach (explode(';;', $raw) as $chunk) {
            $chunk = trim($chunk);
            if ('' === $chunk || !str_contains($chunk, ':')) {
                continue;
            }
            [$qid, $label] = explode(':', $chunk, 2);
            $qid = trim($qid);
            if (preg_match('/^Q\d+$/i', $qid)) {
                $out[] = ['qid' => strtoupper($qid), 'label' => trim($label)];
            }
        }

        return $out;
    }

    /** @return list<array{qid: string, label: string, start: ?string, end: ?string}> */
    private function parsePositionPairs(?string $raw): array
    {
        if (null === $raw || '' === $raw) {
            return [];
        }
        $out = [];
        foreach (explode(';;', $raw) as $chunk) {
            $chunk = trim($chunk);
            if ('' === $chunk) {
                continue;
            }
            $parts = explode(':', $chunk);
            if (\count($parts) < 2) {
                continue;
            }
            $qid = trim($parts[0]);
            if (!preg_match('/^Q\d+$/i', $qid)) {
                continue;
            }
            $label = trim($parts[1] ?? '');
            $start = isset($parts[2]) ? trim($parts[2]) : null;
            $end = isset($parts[3]) ? trim($parts[3]) : null;
            $out[] = [
                'qid' => strtoupper($qid),
                'label' => $label,
                'start' => $start && '' !== $start ? $start : null,
                'end' => $end && '' !== $end ? $end : null,
            ];
        }

        return $out;
    }
}
