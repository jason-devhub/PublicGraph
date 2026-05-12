<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Tests\Support\CatalogPublicFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Parcours visiteur public (kernel HTTP, sans Panther — stable en CI).
 *
 * @group e2e
 */
final class VisitorJourneyTest extends WebTestCase
{
    public function testHomeToPersonShowAndGraphPlaceholder(): void
    {
        $client = static::createClient();
        CatalogPublicFixture::seed($client->getContainer()->get(EntityManagerInterface::class));

        $crawler = $client->request('GET', '/fr/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="home-stat-persons"]');
        self::assertSelectorTextContains('h1', 'Cartographie d’influence');

        $nav = $crawler->filter('nav[aria-label="Navigation principale"]');
        $client->click($nav->selectLink('Personnes')->link());
        self::assertResponseIsSuccessful();
        self::assertStringEndsWith('/people', $client->getRequest()->getPathInfo());

        $client->request('GET', '/fr/people/jean-public');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Jean Public');
        self::assertSelectorTextContains('article', 'Appartenances');
        self::assertSelectorTextContains('article', 'Mandats et postes');
        self::assertSelectorTextContains('article', 'Sources');

        $crawler = $client->getCrawler();
        $client->click($crawler->filter('nav[aria-label="Navigation principale"]')->selectLink('Graphe')->link());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Graphe');
    }
}
