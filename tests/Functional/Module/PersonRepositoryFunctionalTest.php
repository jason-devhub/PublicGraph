<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;

final class PersonRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testFindBySlugReturnsPerson(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix, Person::STATUS_APPROVED);
        $slug = $person->getSlug();

        $repo = $this->getRepository();
        $found = $repo->findBySlug($slug);

        self::assertInstanceOf(Person::class, $found);
        self::assertSame($person->getId(), $found->getId());
    }

    public function testFindApprovedFiltersByStatus(): void
    {
        $suffix = $this->newUserSuffix();
        $approved = $this->persistPerson($suffix.'a', Person::STATUS_APPROVED);
        $this->persistPerson($suffix.'b', Person::STATUS_PENDING);

        $repo = $this->getRepository();
        $approvedList = $repo->findApproved();

        self::assertContainsPersonId($approvedList, (int) $approved->getId());
        foreach ($approvedList as $p) {
            self::assertSame(Person::STATUS_APPROVED, $p->getStatus());
        }
    }

    public function testFindPendingForUser(): void
    {
        $suffix = $this->newUserSuffix();
        $user = $this->persistUser($suffix);
        $pending = $this->persistPerson($suffix.'p', Person::STATUS_PENDING, $user);
        $this->persistPerson($suffix.'o', Person::STATUS_PENDING, null);

        $repo = $this->getRepository();
        $list = $repo->findPendingForUser($user);

        self::assertContainsPersonId($list, (int) $pending->getId());
        foreach ($list as $p) {
            self::assertSame(Person::STATUS_PENDING, $p->getStatus());
            self::assertSame($user->getId(), $p->getCreatedBy()?->getId());
        }
    }

    public function testCountByStatus(): void
    {
        $suffix = $this->newUserSuffix();
        $this->persistPerson($suffix.'1', Person::STATUS_APPROVED);
        $this->persistPerson($suffix.'2', Person::STATUS_APPROVED);
        $this->persistPerson($suffix.'3', Person::STATUS_PENDING);

        $repo = $this->getRepository();
        $counts = $repo->countByStatus();

        self::assertGreaterThanOrEqual(2, $counts[Person::STATUS_APPROVED] ?? 0);
        self::assertGreaterThanOrEqual(1, $counts[Person::STATUS_PENDING] ?? 0);
    }

    private function getRepository(): PersonRepository
    {
        $repo = $this->getEntityManager()->getRepository(Person::class);
        \assert($repo instanceof PersonRepository);

        return $repo;
    }

    /**
     * @param list<Person> $list
     */
    private static function assertContainsPersonId(array $list, int $id): void
    {
        $ids = array_map(static fn (Person $p) => $p->getId(), $list);
        self::assertContains($id, $ids, 'La personne attendue est absente du résultat.');
    }
}
