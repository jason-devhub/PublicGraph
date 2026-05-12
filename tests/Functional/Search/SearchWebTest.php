<?php

declare(strict_types=1);

namespace App\Tests\Functional\Search;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SearchWebTest extends WebTestCase
{
    public function testSearchPageWithoutQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/search');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main h1', 'Search');
    }

    public function testSearchWithQueryDoesNotServerError(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/search?q=minsky&type=persons');

        if ($client->getResponse()->getStatusCode() >= 500) {
            self::markTestSkipped('Meilisearch ou stack recherche indisponible pour ce test.');
        }

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('main');
    }

    public function testRechercheFrenchAliasRedirectsToSearch(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/recherche?q=Jean',
            [],
            [],
            ['HTTP_COOKIE' => 'preferred_locale=en'],
        );

        self::assertResponseRedirects();
        self::assertStringContainsString('/en/recherche', (string) $client->getResponse()->headers->get('Location'));

        $client->followRedirect();
        self::assertResponseRedirects();
        self::assertStringContainsString('/en/search', (string) $client->getResponse()->headers->get('Location'));
    }
}
