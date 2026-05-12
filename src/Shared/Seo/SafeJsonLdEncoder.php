<?php

declare(strict_types=1);

namespace App\Shared\Seo;

/**
 * Encodage JSON-LD sûr pour inclusion dans {@code <script type="application/ld+json">}
 * (évite la rupture de contexte HTML via JSON_HEX_*).
 */
final class SafeJsonLdEncoder
{
    /**
     * @param array<string, mixed> $data
     */
    public static function encodeArray(array $data): string
    {
        return json_encode(
            $data,
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT,
        );
    }
}
