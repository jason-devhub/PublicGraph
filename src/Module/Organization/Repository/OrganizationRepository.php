<?php

declare(strict_types=1);

namespace App\Module\Organization\Repository;

use App\Module\Catalog\Entity\Country;
use App\Module\Organization\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /** @return list<Organization> */
    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['officialName' => 'ASC']);
    }

    public function findOneByWikidataId(string $wikidataId): ?Organization
    {
        return $this->findOneBy(['wikidataId' => strtoupper(trim($wikidataId))]);
    }

    /**
     * Jointures pour indexer Meilisearch (traductions, pays).
     *
     * @return list<Organization>
     */
    public function findApprovedForSearchIndex(): array
    {
        /** @var list<Organization> $result */
        $result = $this->createQueryBuilder('o')
            ->select('o', 'tr', 'c')
            ->distinct()
            ->leftJoin('o.translations', 'tr')
            ->leftJoin('o.countries', 'c')
            ->andWhere('o.status = :approved')
            ->setParameter('approved', Organization::STATUS_APPROVED)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /** @return list<Organization> */
    public function findApproved(): array
    {
        return $this->findBy(['status' => Organization::STATUS_APPROVED], ['officialName' => 'ASC']);
    }

    public function countApprovedPublic(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :approved')
            ->setParameter('approved', Organization::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return \Generator<int, Organization>
     */
    public function iterateApprovedForSitemap(int $batchSize = 1000): \Generator
    {
        $lastId = 0;
        while (true) {
            /** @var list<Organization> $batch */
            $batch = $this->createQueryBuilder('o')
                ->andWhere('o.status = :approved')
                ->andWhere('o.id > :lastId')
                ->setParameter('approved', Organization::STATUS_APPROVED)
                ->setParameter('lastId', $lastId)
                ->orderBy('o.id', 'ASC')
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult();

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $organization) {
                yield $organization;
                $oid = $organization->getId();
                if (null !== $oid) {
                    $lastId = max($lastId, $oid);
                }
            }
        }
    }

    /** @return list<Organization> */
    public function findInfluenceNetworks(): array
    {
        return $this->findByType(Organization::TYPE_INFLUENCE_NETWORK);
    }

    public function createApprovedCatalogListQueryBuilder(?array $types, array $countryIsoCodes): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->andWhere('o.status = :approved')
            ->setParameter('approved', Organization::STATUS_APPROVED)
            ->orderBy('o.officialName', 'ASC');

        if (null !== $types && [] !== $types) {
            $qb->andWhere('o.type IN (:otypes)')
                ->setParameter('otypes', $types);
        }

        if ([] !== $countryIsoCodes) {
            $countryExists = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Country::class, 'fc')
                ->innerJoin('fc.organizations', 'fo')
                ->where('fo = o')
                ->andWhere('fc.isoCode IN (:countryCodes)');
            $qb->andWhere($qb->expr()->exists($countryExists->getDQL()))
                ->setParameter('countryCodes', $countryIsoCodes);
        }

        return $qb;
    }
}
