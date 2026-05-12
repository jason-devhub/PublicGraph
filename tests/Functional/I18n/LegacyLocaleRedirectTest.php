<?php

declare(strict_types=1);

namespace App\Tests\Functional\I18n;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LegacyLocaleRedirectTest extends WebTestCase
{
    public function testPeopleListRedirectsToDefaultLocale(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/people');

        self::assertResponseStatusCodeSame(301);
        self::assertStringContainsString('/en/people', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGraphIndexRedirectsToDefaultLocale(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/graph');

        self::assertResponseStatusCodeSame(301);
        self::assertStringContainsString('/en/graph', (string) $client->getResponse()->headers->get('Location'));
    }
}
