<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testArithmeticBaseline(): void
    {
        self::assertSame(2, 1 + 1);
    }
}
