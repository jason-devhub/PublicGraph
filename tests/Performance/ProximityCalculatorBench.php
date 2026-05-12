<?php

declare(strict_types=1);

namespace App\Tests\Performance;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Benchmark manuel : lancer avec `phpunit tests/Performance/ProximityCalculatorBench.php`.
 * Non exécuté dans la suite CI standard (données volumineuses).
 */
#[Group('perf')]
final class ProximityCalculatorBench extends TestCase
{
    public function testPlaceholderForDocumentation(): void
    {
        self::markTestSkipped('Benchmark ProximityCalculator : exécuter localement avec dataset Foundry (T5.9).');
    }
}
