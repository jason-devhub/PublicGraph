<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Influence\Entity\Position;
use App\Module\Influence\Repository\PositionRepository;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;

final class PositionRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testFindCurrentForPerson(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $past = $this->persistPosition($person, $org, new \DateTimeImmutable('-2 years'), new \DateTimeImmutable('-1 year'));
        $current = $this->persistPosition($person, $org, new \DateTimeImmutable('-30 days'), null);

        $repo = $this->getRepository();
        $currentList = $repo->findCurrentForPerson($person);

        self::assertContainsPositionId($currentList, (int) $current->getId());
        foreach ($currentList as $pos) {
            self::assertTrue($pos->getStartDate() <= new \DateTimeImmutable('today'));
            $end = $pos->getEndDate();
            self::assertTrue(null === $end || $end >= new \DateTimeImmutable('today'));
        }
        self::assertNotContainsPositionId($currentList, (int) $past->getId());
    }

    public function testFindByPersonOrdersByStartDateDesc(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $older = $this->persistPosition($person, $org, new \DateTimeImmutable('2020-01-01'), new \DateTimeImmutable('2020-12-31'));
        $newer = $this->persistPosition($person, $org, new \DateTimeImmutable('2022-01-01'), null);

        $repo = $this->getRepository();
        $list = $repo->findByPerson($person);

        self::assertGreaterThan(0, \count($list));
        self::assertSame((int) $newer->getId(), (int) $list[0]->getId());
        self::assertContainsPositionId($list, (int) $older->getId());
    }

    public function testFindOverlappingSamePerson(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $a = $this->persistPosition($person, $org, new \DateTimeImmutable('2023-06-01'), new \DateTimeImmutable('2023-12-31'));
        $b = $this->persistPosition($person, $org, new \DateTimeImmutable('2023-09-01'), new \DateTimeImmutable('2024-01-15'));

        $repo = $this->getRepository();
        self::assertTrue($repo->findOverlapping($a, $b));
    }

    public function testFindOverlappingDifferentPersonsReturnsFalse(): void
    {
        $suffix = $this->newUserSuffix();
        $p1 = $this->persistPerson($suffix.'1', Person::STATUS_APPROVED);
        $p2 = $this->persistPerson($suffix.'2', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $a = $this->persistPosition($p1, $org, new \DateTimeImmutable('2023-01-01'), null);
        $b = $this->persistPosition($p2, $org, new \DateTimeImmutable('2023-01-01'), null);

        $repo = $this->getRepository();
        self::assertFalse($repo->findOverlapping($a, $b));
    }

    private function persistPosition(Person $person, Organization $org, \DateTimeImmutable $start, ?\DateTimeImmutable $end): Position
    {
        $pos = new Position();
        $pos->setPerson($person);
        $pos->setOrganization($org);
        $pos->setTitleFr('Mandat test');
        $pos->setNature(Position::NATURE_OTHER);
        $pos->setStartDate($start);
        $pos->setEndDate($end);
        $this->getEntityManager()->persist($pos);
        $this->getEntityManager()->flush();

        return $pos;
    }

    private function getRepository(): PositionRepository
    {
        $repo = $this->getEntityManager()->getRepository(Position::class);
        \assert($repo instanceof PositionRepository);

        return $repo;
    }

    /**
     * @param list<Position> $list
     */
    private static function assertContainsPositionId(array $list, int $id): void
    {
        $ids = array_map(static fn (Position $p) => $p->getId(), $list);
        self::assertContains($id, $ids);
    }

    /**
     * @param list<Position> $list
     */
    private static function assertNotContainsPositionId(array $list, int $id): void
    {
        $ids = array_map(static fn (Position $p) => $p->getId(), $list);
        self::assertNotContains($id, $ids);
    }
}
