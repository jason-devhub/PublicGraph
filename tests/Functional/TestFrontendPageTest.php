<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TestFrontendPageTest extends WebTestCase
{
    public function testTestFrontendPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/test-frontend');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Configuration frontend');
        self::assertSelectorExists('[data-controller="hello"]');
    }
}
