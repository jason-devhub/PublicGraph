<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomePageTest extends WebTestCase
{
    public function testRootRedirectsToDefaultLocaleHome(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/');

        self::assertResponseRedirects();
        self::assertResponseRedirects('/en/');
    }

    public function testHomePageUsesBaseLayout(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorExists('[data-testid="home-stat-persons"]');
        self::assertSelectorExists('[data-testid="home-stat-organizations"]');
        self::assertSelectorExists('[data-testid="home-stat-doors"]');
        self::assertSelectorTextContains('header a[href="/en/"]', 'PublicGraph');
        self::assertSelectorTextContains('h1', 'Factual influence mapping');
        self::assertSelectorTextContains('nav[aria-label="Main navigation"]', 'People');
        self::assertSelectorTextContains('nav[aria-label="Main navigation"]', 'Organizations');
        self::assertSelectorTextContains('nav[aria-label="Main navigation"]', 'Graph');
        self::assertSelectorTextContains('nav[aria-label="Main navigation"]', 'Search');
        self::assertSelectorTextContains('nav[aria-label="Main navigation"]', 'Log in');
        self::assertSelectorTextContains('footer', 'Legal notice');
        self::assertSelectorTextContains('footer', 'Terms of use');
        self::assertSelectorTextContains('footer', 'Privacy');
        self::assertSelectorTextContains('footer', 'Editorial charter');
        self::assertSelectorTextContains('footer', 'About');
        self::assertSelectorTextContains('footer', 'Report content');
        self::assertSelectorTextContains('footer', 'Right of reply');
        self::assertSelectorTextContains('footer', 'Contact');
        self::assertSelectorTextContains('aside[aria-label="Cookies"]', 'cookies');
    }

    public function testFooterLegalLinksReturnSuccessfulPages(): void
    {
        $client = static::createClient();
        foreach ([
            '/en/legal-notice',
            '/en/terms',
            '/en/privacy',
            '/en/editorial-charter',
            '/en/about',
            '/en/report',
            '/en/right-of-reply',
            '/en/contact',
        ] as $path) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful(\sprintf('Réponse attendue pour %s', $path));
        }
    }
}
