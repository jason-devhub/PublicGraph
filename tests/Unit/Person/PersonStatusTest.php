<?php

declare(strict_types=1);

namespace App\Tests\Unit\Person;

use App\Module\Person\Entity\Person;
use PHPUnit\Framework\TestCase;

final class PersonStatusTest extends TestCase
{
    public function testDraftToPendingAllowed(): void
    {
        self::assertTrue(Person::isAllowedStatusTransition(Person::STATUS_DRAFT, Person::STATUS_PENDING));
    }

    public function testArchivedToApprovedRejected(): void
    {
        self::assertFalse(Person::isAllowedStatusTransition(Person::STATUS_ARCHIVED, Person::STATUS_APPROVED));
    }
}
