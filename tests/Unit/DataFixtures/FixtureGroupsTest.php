<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\DevFixtures;
use App\DataFixtures\MinimalFixtures;
use PHPUnit\Framework\TestCase;

final class FixtureGroupsTest extends TestCase
{
    public function testMinimalFixturesBelongsToTestGroup(): void
    {
        self::assertSame(['test'], MinimalFixtures::getGroups());
    }

    public function testDevFixturesBelongsToDevGroup(): void
    {
        self::assertSame(['dev'], DevFixtures::getGroups());
    }
}
