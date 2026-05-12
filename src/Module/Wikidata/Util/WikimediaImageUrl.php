<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Util;

/**
 * Construit une URL de miniature Commons depuis le titre de fichier P18.
 */
final class WikimediaImageUrl
{
    public static function buildThumbnail(string $filename, int $width = 250): string
    {
        $name = preg_replace('#^File:#i', '', trim($filename));
        $name = str_replace(' ', '_', (string) $name);

        return 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($name).'?width='.$width;
    }
}
