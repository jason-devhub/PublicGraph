<?php

declare(strict_types=1);

namespace App\Tests\Unit\Legislation;

use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use PHPUnit\Framework\TestCase;

final class RevolvingDoorDelayTest extends TestCase
{
    public function testDelayDaysComputedFromEndToStart(): void
    {
        $person = new Person();
        $org = new Organization();
        $org->setOfficialName('Org A');
        $org->setType(Organization::TYPE_CORPORATION);

        $p1 = new Position();
        $p1->setPerson($person);
        $p1->setOrganization($org);
        $p1->setTitleFr('Mandat');
        $p1->setNature(Position::NATURE_ELECTED_OFFICE);
        $p1->setStartDate(new \DateTimeImmutable('2010-01-01'));
        $p1->setEndDate(new \DateTimeImmutable('2020-01-01'));

        $p2 = new Position();
        $p2->setPerson($person);
        $p2->setOrganization($org);
        $p2->setTitleFr('Privé');
        $p2->setNature(Position::NATURE_CORPORATE_POSITION);
        $p2->setStartDate(new \DateTimeImmutable('2020-02-15'));

        $rd = new RevolvingDoor();
        $rd->setPerson($person);
        $rd->setSourcePosition($p1);
        $rd->setTargetPosition($p2);
        $rd->refreshDelayDays();

        self::assertSame(45, $rd->getDelayDays());
    }
}
