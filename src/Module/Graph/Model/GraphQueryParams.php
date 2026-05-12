<?php

declare(strict_types=1);

namespace App\Module\Graph\Model;

use Symfony\Component\HttpFoundation\Request;

final class GraphQueryParams
{
    /**
     * @param list<string> $countryIsoCodes
     * @param list<string> $roleCategories
     */
    public function __construct(
        public ?string $organizationSlug = null,
        public array $countryIsoCodes = [],
        public array $roleCategories = [],
        public ?int $yearMin = null,
        public ?int $yearMax = null,
        public int $maxNodes = 100,
        public string $colorMode = 'category',
        public string $locale = 'en',
    ) {
        $this->maxNodes = max(25, min(200, $this->maxNodes));
    }

    public static function fromRequest(Request $request): self
    {
        $max = (int) $request->query->get('maxNodes', '100');
        $yearMin = $request->query->get('yearMin');
        $yearMax = $request->query->get('yearMax');

        $countries = self::parseCommaList($request->query->get('countries'));
        $categories = self::parseCommaList($request->query->get('categories'));

        return new self(
            organizationSlug: self::stringOrNull($request->query->get('organization')),
            countryIsoCodes: $countries,
            roleCategories: $categories,
            yearMin: is_numeric($yearMin) ? (int) $yearMin : null,
            yearMax: is_numeric($yearMax) ? (int) $yearMax : null,
            maxNodes: $max,
            colorMode: \in_array($request->query->get('colorMode'), ['category', 'country'], true)
                ? (string) $request->query->get('colorMode')
                : 'category',
            locale: $request->getLocale(),
        );
    }

    /**
     * @return list<string>
     */
    private static function parseCommaList(mixed $raw): array
    {
        if (!\is_string($raw) || '' === trim($raw)) {
            return [];
        }
        $parts = array_map('trim', explode(',', $raw));
        $out = [];
        foreach ($parts as $p) {
            if ('' !== $p) {
                $out[] = strtoupper($p);
            }
        }

        return $out;
    }

    private static function stringOrNull(mixed $v): ?string
    {
        if (!\is_string($v) || '' === trim($v)) {
            return null;
        }

        return trim($v);
    }
}
