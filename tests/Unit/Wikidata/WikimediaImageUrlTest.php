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
}
