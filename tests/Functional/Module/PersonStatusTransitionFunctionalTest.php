<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Person\Entity\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PersonStatusTransitionFunctionalTest extends TestCase
{
    public static function provideAllowedTransitions(): iterable
    {
        yield 'draft to pending' => [Person::STATUS_DRAFT, Person::STATUS_PENDING];
        yield 'pending to approved' => [Person::STATUS_PENDING, Person::STATUS_APPROVED];
        yield 'pending to rejected' => [Person::STATUS_PENDING, Person::STATUS_REJECTED];
        yield 'pending to draft' => [Person::STATUS_PENDING, Person::STATUS_DRAFT];
        yield 'approved to archived' => [Person::STATUS_APPROVED, Person::STATUS_ARCHIVED];
        yield 'rejected to draft' => [Person::STATUS_REJECTED, Person::STATUS_DRAFT];
        yield 'rejected to pending' => [Person::STATUS_REJECTED, Person::STATUS_PENDING];
        yield 'same status' => [Person::STATUS_PENDING, Person::STATUS_PENDING];
    }

    public static function provideForbiddenTransitions(): iterable
    {
        yield 'draft to approved' => [Person::STATUS_DRAFT, Person::STATUS_APPROVED];
        yield 'archived to pending' => [Person::STATUS_ARCHIVED, Person::STATUS_PENDING];
        yield 'approved to pending' => [Person::STATUS_APPROVED, Person::STATUS_PENDING];
        yield 'unknown from' => ['unknown', Person::STATUS_APPROVED];
    }

    #[DataProvider('provideAllowedTransitions')]
    public function testAllowed(string $from, string $to): void
    {
        self::assertTrue(Person::isAllowedStatusTransition($from, $to));
    }

    #[DataProvider('provideForbiddenTransitions')]
    public function testForbidden(string $from, string $to): void
    {
        self::assertFalse(Person::isAllowedStatusTransition($from, $to));
    }
}
