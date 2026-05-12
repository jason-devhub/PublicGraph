<?php

declare(strict_types=1);

namespace App\Module\Source\Repository;

use App\Module\Source\Entity\Source;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Source>
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Source::class);
    }

    public function findOneWikidataItem(string $wikidataUrl): ?Source
    {
        return $this->findOneBy(['url' => $wikidataUrl, 'type' => Source::TYPE_WIKIDATA]);
    }

    /**
     * @return list<Source>
     */
    public function findBatchForUrlCheck(int $limit = 200): array
    {
        $threshold = new \DateTimeImmutable('-7 days');

        return $this->createQueryBuilder('s')
            ->andWhere('s.lastCheckedAt IS NULL OR s.lastCheckedAt < :threshold')
            ->orderBy('s.lastCheckedAt', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
