<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Parcours Panther (T4.10) : exécuter dans l’environnement Docker avec Chrome/Chromium.
 *
 * Exemple : docker compose exec publicgraph-php php bin/phpunit --group e2e
 */
#[Group('e2e')]
final class ContributionM4PantherPlaceholderTest extends TestCase
{
    public function testPantherSuiteRequiresDockerBrowser(): void
    {
        self::markTestSkipped('Configurer Panther + navigateur dans le conteneur publicgraph-php pour les parcours E2E complets.');
    }
}
