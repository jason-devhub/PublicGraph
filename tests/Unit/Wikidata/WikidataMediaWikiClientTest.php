<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wikidata;

use App\Module\Wikidata\Client\WikidataMediaWikiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WikidataMediaWikiClientTest extends TestCase
{
    public function testQidFromSiteTitleParsesEntityId(): void
    {
        $json = <<<'JSON'
{
  "entities": {
    "Q3227220": {
      "id": "Q3227220",
      "type": "item",
      "labels": { "fr": { "language": "fr", "value": "Le Siècle" } }
    }
  },
  "success": 1
}
JSON;
        $client = new MockHttpClient([
            new MockResponse($json, ['http_code' => 200]),
        ]);
        $mw = new WikidataMediaWikiClient($client);
        self::assertSame('Q3227220', $mw->qidFromSiteTitle('frwiki', 'Le_Siècle'));
    }

    public function testQidFromSiteTitleReturnsNullOnMissingEntity(): void
    {
        $json = '{"entities":{"-1":{"missing":""}},"success":1}';
        $client = new MockHttpClient([
            new MockResponse($json, ['http_code' => 200]),
        ]);
        $mw = new WikidataMediaWikiClient($client);
        self::assertNull($mw->qidFromSiteTitle('frwiki', 'Page_Inexistante_XYZ'));
    }
}
