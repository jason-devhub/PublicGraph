<?php

declare(strict_types=1);

namespace App\Module\Legislation\Repository;

use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Person\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RevolvingDoor>
 */
class RevolvingDoorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RevolvingDoor::class);
    }

    public function countApproved(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :approved')
            ->setParameter('approved', 'approved')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Portes tournantes publiées récentes (personne approuvée).
     *
     * @return list<RevolvingDoor>
     */
    public function findLatestApprovedPublic(int $limit = 3): array
    {
        /** @var list<RevolvingDoor> $rows */
        $rows = $this->createQueryBuilder('r')
            ->innerJoin('r.person', 'rp')
            ->addSelect('rp')
            ->andWhere('r.status = :approved')
            ->andWhere('rp.status = :papproved')
            ->andWhere('rp.deletedAt IS NULL')
            ->setParameter('approved', 'approved')
            ->setParameter('papproved', Person::STATUS_APPROVED)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return $rows;
    }

    public function findOneApprovedById(int $id): ?RevolvingDoor
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.id = :id')
            ->andWhere('r.status = :approved')
            ->setParameter('id', $id)
            ->setParameter('approved', 'approved')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return \Generator<int, RevolvingDoor>
     */
    public function iterateApprovedForSitemap(int $batchSize = 500): \Generator
    {
        $lastId = 0;
        while (true) {
            /** @var list<RevolvingDoor> $batch */
            $batch = $this->createQueryBuilder('r')
                ->andWhere('r.status = :approved')
                ->andWhere('r.id > :lastId')
                ->setParameter('approved', 'approved')
                ->setParameter('lastId', $lastId)
                ->orderBy('r.id', 'ASC')
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult();

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $door) {
                yield $door;
                $rid = $door->getId();
                if (null !== $rid) {
                    $lastId = max($lastId, $rid);
                }
            }
        }
    }
}
