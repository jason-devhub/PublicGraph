<?php

declare(strict_types=1);

namespace App\Module\Influence\Repository;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 */
class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    /** @return list<Membership> */
    public function findByPerson(Person $person): array
    {
        return $this->findBy(['person' => $person], ['year' => 'DESC', 'startDate' => 'DESC']);
    }

    /** @return list<Membership> */
    public function findByOrganization(Organization $organization): array
    {
        return $this->findBy(['organization' => $organization], ['year' => 'DESC']);
    }

    /** @return list<Membership> */
    public function findByYear(int $year): array
    {
        return $this->findBy(['year' => $year]);
    }
}
