<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

/**
 * Lit les cellules typées d’une ligne SPARQL JSON (WDQS).
 *
 * @phpstan-type BindingRow array<string, array{type: string, value: string}>
 */
final class WikidataBindingValue
{
    /** @param BindingRow $row */
    public static function optionalString(array $row, string $key): ?string
    {
        if (!isset($row[$key]['value'])) {
            return null;
        }
        $v = (string) $row[$key]['value'];

        return '' === trim($v) ? null : trim($v);
    }

    /** @param BindingRow $row */
    public static function optionalDateImmutable(array $row, string $key): ?\DateTimeImmutable
    {
        $raw = self::optionalString($row, $key);
        if (null === $raw) {
            return null;
        }
        try {
            if (preg_match('/^(\d{4})$/', $raw, $m)) {
                return new \DateTimeImmutable($m[1].'-01-01');
            }
            if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $m)) {
                return new \DateTimeImmutable($m[1].'-'.$m[2].'-01');
            }

            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }
}
