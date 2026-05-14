<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthEndpointTest extends WebTestCase
{
    public function testHealthReturnsJsonWithDatabaseOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        $contentType = (string) $client->getResponse()->headers->get('content-type');
        self::assertStringContainsString('application/json', $contentType);
        $raw = $client->getResponse()->getContent();
        self::assertIsString($raw);
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ok', $data['status']);
        self::assertSame('ok', $data['database']);
    }

    public function testHealthIsNotLocaleRedirected(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertResponseNotHasHeader('location');
    }
}
