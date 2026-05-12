<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Client;

/**
 * Correspondance ISO 3166-1 alpha-2 → QID nationalité Wikidata (échantillon extensible).
 */
final class WikidataCountryQids
{
    /** @var array<string, list<string>> */
    private const ISO_TO_NAT_QIDS = [
        'FR' => ['Q142'],
        'DE' => ['Q183'],
        'IT' => ['Q38'],
        'ES' => ['Q29'],
        'US' => ['Q30'],
        'GB' => ['Q145'],
        'CA' => ['Q16'],
        'JP' => ['Q17'],
        'BE' => ['Q31'],
        'NL' => ['Q55'],
        'PL' => ['Q36'],
        'PT' => ['Q45'],
        'SE' => ['Q34'],
        'AT' => ['Q40'],
        'IE' => ['Q27'],
        'GR' => ['Q41'],
        'CZ' => ['Q213'],
        'RO' => ['Q218'],
        'HU' => ['Q28'],
        'SK' => ['Q214'],
        'SI' => ['Q215'],
        'HR' => ['Q224'],
        'BG' => ['Q219'],
        'LU' => ['Q32'],
        'MT' => ['Q233'],
        'CY' => ['Q229'],
        'EE' => ['Q191'],
        'LV' => ['Q211'],
        'LT' => ['Q37'],
        'FI' => ['Q33'],
        'DK' => ['Q35'],
    ];

    /** @return list<string> QIDs sans préfixe wd: */
    public static function nationalityQidsForIso(string $iso): array
    {
        return self::ISO_TO_NAT_QIDS[strtoupper($iso)] ?? [];
    }

    /** @return list<string> */
    public static function g7NationalityQids(): array
    {
        $out = [];
        foreach (['US', 'JP', 'DE', 'FR', 'GB', 'IT', 'CA'] as $iso) {
            foreach (self::nationalityQidsForIso($iso) as $q) {
                $out[] = $q;
            }
        }

        return array_values(array_unique($out));
    }

    /** @return list<string> Union des pays UE listés */
    public static function euNationalityQids(): array
    {
        $codes = ['FR', 'DE', 'IT', 'ES', 'NL', 'BE', 'PL', 'SE', 'AT', 'IE', 'GR', 'PT', 'CZ', 'RO', 'HU', 'SK', 'SI', 'HR', 'BG', 'LU', 'MT', 'CY', 'EE', 'LV', 'LT', 'FI', 'DK'];
        $out = [];
        foreach ($codes as $iso) {
            foreach (self::nationalityQidsForIso($iso) as $q) {
                $out[] = $q;
            }
        }

        return array_values(array_unique($out));
    }

    public static function isoForNationalityQid(string $nationalityQid): ?string
    {
        $needle = strtoupper(trim($nationalityQid));
        foreach (self::ISO_TO_NAT_QIDS as $iso => $qids) {
            foreach ($qids as $q) {
                if (strtoupper($q) === $needle) {
                    return $iso;
                }
            }
        }

        return null;
    }
}
