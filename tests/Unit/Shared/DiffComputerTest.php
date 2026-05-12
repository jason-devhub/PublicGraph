<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Service\DiffComputer;
use PHPUnit\Framework\TestCase;

final class DiffComputerTest extends TestCase
{
    public function testDetectsChangeOnPrivateProperty(): void
    {
        $a = new DiffComputerTestDto(1);
        $b = new DiffComputerTestDto(2);
        $diff = (new DiffComputer())->compute($a, $b);
        self::assertArrayHasKey('v', $diff);
    }
}

/** @internal */
final class DiffComputerTestDto
{
    public function __construct(private int $v)
    {
    }
}
