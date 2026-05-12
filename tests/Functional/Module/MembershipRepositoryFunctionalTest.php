<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Repository\MembershipRepository;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;

final class MembershipRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testFindByPersonAndOrganizationAndYear(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');
        $org2 = $this->persistOrganization($suffix.'o2', Organization::TYPE_OTHER, 'approved');

        $m2019 = $this->persistMembership($person, $org, 2019);
        $m2020 = $this->persistMembership($person, $org2, 2020);

        $repo = $this->getRepository();

        $byPerson = $repo->findByPerson($person);
        self::assertContainsMembershipId($byPerson, (int) $m2020->getId());
        self::assertContainsMembershipId($byPerson, (int) $m2019->getId());

        $byOrg = $repo->findByOrganization($org);
        self::assertContainsMembershipId($byOrg, (int) $m2019->getId());

        $byYear = $repo->findByYear(2020);
        self::assertContainsMembershipId($byYear, (int) $m2020->getId());
    }

    private function persistMembership(Person $person, Organization $org, int $year): Membership
    {
        $m = new Membership();
        $m->setPerson($person);
        $m->setOrganization($org);
        $m->setYear($year);
        $this->getEntityManager()->persist($m);
        $this->getEntityManager()->flush();

        return $m;
    }

    private function getRepository(): MembershipRepository
    {
        $repo = $this->getEntityManager()->getRepository(Membership::class);
        \assert($repo instanceof MembershipRepository);

        return $repo;
    }

    /**
     * @param list<Membership> $list
     */
    private static function assertContainsMembershipId(array $list, int $id): void
    {
        $ids = array_map(static fn (Membership $m) => $m->getId(), $list);
        self::assertContains($id, $ids);
    }
}
