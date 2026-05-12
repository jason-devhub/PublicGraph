<?php

declare(strict_types=1);

namespace App\Module\Influence\Repository;

use App\Module\Influence\Entity\Position;
use App\Module\Person\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Position>
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    /** @return list<Position> */
    public function findCurrentForPerson(Person $person): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('p')
            ->andWhere('p.person = :person')
            ->andWhere('p.startDate <= :today')
            ->andWhere('p.endDate IS NULL OR p.endDate >= :today')
            ->setParameter('person', $person)
            ->setParameter('today', $today)
            ->orderBy('p.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Position> */
    public function findByPerson(Person $person): array
    {
        return $this->findBy(['person' => $person], ['startDate' => 'DESC']);
    }

    public function findOverlapping(Position $a, Position $b): bool
    {
        if ($a->getPerson()->getId() !== $b->getPerson()->getId()) {
            return false;
        }

        $aStart = $a->getStartDate();
        $aEnd = $a->getEndDate() ?? new \DateTimeImmutable('9999-12-31');
        $bStart = $b->getStartDate();
        $bEnd = $b->getEndDate() ?? new \DateTimeImmutable('9999-12-31');

        return $aStart <= $bEnd && $bStart <= $aEnd;
    }
}
