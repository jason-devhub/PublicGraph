<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Pages publiques sans accès base (pas de soumission de formulaire : UniqueEntity requiert MySQL).
 * Scénarios inscription/connexion complets : exécuter la suite dans Docker avec base migrée.
 */
final class AuthenticationPagesTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/login');
        self::assertResponseIsSuccessful();
    }

    public function testRegistrationPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/register');
        self::assertResponseIsSuccessful();
    }
}
