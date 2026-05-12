<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapPagesTest extends WebTestCase
{
    public function testSitemapIndexIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertTrue(
            str_contains($body, 'urlset') || str_contains($body, 'sitemapindex'),
            'Réponse sitemap attendue (index ou urlset).',
        );
    }

    public function testLegacySitemapPersonsRedirectsToPrestaSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap-persons.xml');
        self::assertResponseRedirects('/sitemap.persons.xml');
    }
}
