<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Shared\Service\DiffComputer;
use PHPUnit\Framework\TestCase;

final class DiffComputerFunctionalTest extends TestCase
{
    public function testIdenticalObjectsYieldEmptyDiff(): void
    {
        $before = new DiffComputerSample('a', 1, ['x' => 1], null);
        $after = new DiffComputerSample('a', 1, ['x' => 1], null);

        $diff = (new DiffComputer())->compute($before, $after);
        self::assertSame([], $diff);
    }

    public function testScalarModification(): void
    {
        $before = new DiffComputerSample('a', 1, ['x' => 1], null);
        $after = new DiffComputerSample('b', 1, ['x' => 1], null);

        $diff = (new DiffComputer())->compute($before, $after);
        self::assertArrayHasKey('name', $diff);
        self::assertSame('a', $diff['name']['old']);
        self::assertSame('b', $diff['name']['new']);
    }

    public function testAdditionAndRemoval(): void
    {
        $before = new DiffComputerSample('a', 1, ['x' => 1], 'present');
        $after = new DiffComputerSample('a', 1, ['x' => 1], null);

        $diff = (new DiffComputer())->compute($before, $after);
        self::assertArrayHasKey('optional', $diff);
        self::assertSame('present', $diff['optional']['old']);
        self::assertNull($diff['optional']['new']);

        $diff2 = (new DiffComputer())->compute($after, $before);
        self::assertArrayHasKey('optional', $diff2);
        self::assertNull($diff2['optional']['old']);
        self::assertSame('present', $diff2['optional']['new']);
    }

    public function testNestedArrayChange(): void
    {
        $before = new DiffComputerSample('a', 1, ['x' => 1, 'y' => 2], null);
        $after = new DiffComputerSample('a', 1, ['x' => 1, 'y' => 3], null);

        $diff = (new DiffComputer())->compute($before, $after);
        self::assertArrayHasKey('payload', $diff);
        self::assertSame(['x' => 1, 'y' => 2], $diff['payload']['old']);
        self::assertSame(['x' => 1, 'y' => 3], $diff['payload']['new']);
    }

    public function testDifferentClassesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DiffComputer())->compute(new DiffComputerSample('a', 1, [], null), new \stdClass());
    }
}

/** @internal */
final class DiffComputerSample
{
    public function __construct(
        public string $name,
        public int $count,
        /** @var array<string, int> */
        public array $payload,
        public ?string $optional,
    ) {
    }
}
