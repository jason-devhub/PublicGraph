<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Recherche et alias d’URL (le header utilise un formulaire GET, pas l’autocomplete Meilisearch).
 *
 * @group e2e
 */
final class SearchTest extends WebTestCase
{
    public function testRechercheRedirectsToSearchWithQuery(): void
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
        $target = (string) $client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/en/recherche', $target);
        self::assertStringContainsString('Jean', $target);

        $client->followRedirect();
        self::assertResponseRedirects();
        $target2 = (string) $client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/en/search', $target2);
        self::assertStringContainsString('Jean', $target2);

        $client->followRedirect();
        if ($client->getResponse()->getStatusCode() >= 500) {
            self::markTestSkipped('Meilisearch ou stack recherche indisponible pour ce test.');
        }
        self::assertResponseIsSuccessful();
    }

    public function testHeaderSearchFormSubmitsToSearchPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('OK')->form(['q' => 'dupont']);
        $client->submit($form);
        if ($client->getResponse()->getStatusCode() >= 500) {
            self::markTestSkipped('Meilisearch ou stack recherche indisponible pour ce test.');
        }
        self::assertResponseIsSuccessful();
        self::assertStringEndsWith('/search', $client->getRequest()->getPathInfo());
        self::assertSame('dupont', $client->getRequest()->query->get('q'));
    }
}
