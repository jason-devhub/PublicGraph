<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wikidata;

use App\Module\Wikidata\Util\WikimediaImageUrl;
use PHPUnit\Framework\TestCase;

final class WikimediaImageUrlTest extends TestCase
{
    public function testBuildThumbnailStripsFilePrefixAndAddsWidth(): void
    {
        $url = WikimediaImageUrl::buildThumbnail('File:Example.jpg', 250);

        self::assertStringContainsString('commons.wikimedia.org', $url);
        self::assertStringContainsString('width=250', $url);
        self::assertStringNotContainsString('File:', $url);
    }

    public function testBuildThumbnailAcceptsWikidataP18CommonsUri(): void
    {
        $uri = 'http://commons.wikimedia.org/wiki/Special:FilePath/Douglas%20adams%20portrait.jpg';
        $url = WikimediaImageUrl::buildThumbnail($uri, 200);

        self::assertStringStartsWith('https://commons.wikimedia.org/wiki/Special:FilePath/', $url);
        self::assertStringContainsString('width=200', $url);
        self::assertStringContainsString('Douglas', $url);
    }
}
