<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GraphDataApiTest extends WebTestCase
{
    public function testGraphDataReturnsJsonElements(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/api/graph/data?maxNodes=10');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('elements', $data);
        self::assertArrayHasKey('nodes', $data['elements']);
        self::assertArrayHasKey('edges', $data['elements']);
    }
}
