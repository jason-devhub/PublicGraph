<?php

declare(strict_types=1);

namespace App\Module\Person\Repository;

use App\Module\Catalog\Entity\Country;
use App\Module\Catalog\Model\PersonCatalogFilterModel;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Person>
 */
class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function findBySlug(string $slug): ?Person
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findOneByWikidataId(string $wikidataId): ?Person
    {
        return $this->findOneBy(['wikidataId' => strtoupper(trim($wikidataId))]);
    }

    /**
     * Personnes liées à Wikidata, les moins récemment synchronisées en premier.
     *
     * @return list<Person>
     */
    public function findBatchForWikidataResync(int $limit): array
    {
        /** @var list<Person> $res */
        $res = $this->createQueryBuilder('p')
            ->andWhere('p.wikidataId IS NOT NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.lastWikidataSyncAt', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return $res;
    }

    /**
     * Jointures pour indexer Meilisearch (traductions, pays, appartenances).
     *
     * @return list<Person>
     */
    public function findApprovedForSearchIndex(): array
    {
        /** @var list<Person> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p', 't', 'n', 'm', 'mo')
            ->distinct()
            ->leftJoin('p.translations', 't')
            ->leftJoin('p.nationalities', 'n')
            ->leftJoin('p.memberships', 'm')
            ->leftJoin('m.organization', 'mo')
            ->andWhere('p.status = :approved')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('approved', Person::STATUS_APPROVED)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /** @return list<Person> */
    public function findApproved(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :approved')
            ->setParameter('approved', Person::STATUS_APPROVED)
            ->orderBy('p.familyName', 'ASC')
            ->addOrderBy('p.givenName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countApprovedPublic(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :approved')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('approved', Person::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Person>
     */
    public function findLatestApprovedPublic(int $limit = 3): array
    {
        /** @var list<Person> $rows */
        $rows = $this->createQueryBuilder('p')
            ->andWhere('p.status = :approved')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('approved', Person::STATUS_APPROVED)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @return \Generator<int, Person>
     */
    public function iterateApprovedForSitemap(int $batchSize = 1000): \Generator
    {
        $lastId = 0;
        while (true) {
            /** @var list<Person> $batch */
            $batch = $this->createQueryBuilder('p')
                ->andWhere('p.status = :approved')
                ->andWhere('p.deletedAt IS NULL')
                ->andWhere('p.id > :lastId')
                ->setParameter('approved', Person::STATUS_APPROVED)
                ->setParameter('lastId', $lastId)
                ->orderBy('p.id', 'ASC')
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult();

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $person) {
                yield $person;
                $pid = $person->getId();
                if (null !== $pid) {
                    $lastId = max($lastId, $pid);
                }
            }
        }
    }

    /** @return list<Person> */
    public function findPendingForUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :pending')
            ->andWhere('p.createdBy = :user')
            ->setParameter('pending', Person::STATUS_PENDING)
            ->setParameter('user', $user)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.status AS s', 'COUNT(p.id) AS c')
            ->groupBy('p.status')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['s']] = (int) $row['c'];
        }

        return $out;
    }

    public function createApprovedCatalogQueryBuilder(PersonCatalogFilterModel $filters): QueryBuilder
    {
        $approvedLink = 'approved';
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->distinct()
            ->andWhere('p.status = :personApproved')
            ->setParameter('personApproved', Person::STATUS_APPROVED);

        if ('recent' === $filters->sort) {
            $qb->orderBy('p.createdAt', 'DESC');
        } else {
            $qb->orderBy('p.familyName', 'ASC')
                ->addOrderBy('p.givenName', 'ASC');
        }

        if ([] !== $filters->countries) {
            $countryExists = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Country::class, 'fc')
                ->innerJoin('fc.persons', 'fp')
                ->where('fp = p')
                ->andWhere('fc.isoCode IN (:countryCodes)');
            $qb->andWhere($qb->expr()->exists($countryExists->getDQL()))
                ->setParameter('countryCodes', $filters->countries);
        }

        if ([] !== $filters->roleCategories) {
            $orX = $qb->expr()->orX();
            foreach ($filters->roleCategories as $i => $cat) {
                $param = 'rc_'.$i;
                $orX->add($qb->expr()->like('p.roleCategories', ':'.$param));
                $qb->setParameter($param, '%'.$cat.'%');
            }
            $qb->andWhere($orX);
        }

        if (null !== $filters->organization) {
            $org = $filters->organization;
            $subMem = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Membership::class, 'sf_m')
                ->where('sf_m.person = p')
                ->andWhere('sf_m.organization = :filterOrg')
                ->andWhere('sf_m.status = :linkApproved');
            $subPos = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Position::class, 'sf_p')
                ->where('sf_p.person = p')
                ->andWhere('sf_p.organization = :filterOrg')
                ->andWhere('sf_p.status = :linkApproved');
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->exists($subMem->getDQL()),
                $qb->expr()->exists($subPos->getDQL()),
            ))
                ->setParameter('filterOrg', $org)
                ->setParameter('linkApproved', $approvedLink);
        }

        if (null !== $filters->party) {
            $partyId = $filters->party->getId();
            if (null !== $partyId) {
                $subParty = $this->getEntityManager()->createQueryBuilder()
                    ->select('1')
                    ->from(Position::class, 'sf_pp')
                    ->innerJoin('sf_pp.organization', 'sf_po')
                    ->where('sf_pp.person = p')
                    ->andWhere('sf_po.id = :partyOrgId')
                    ->andWhere('sf_po.type = :politicalPartyType')
                    ->andWhere('sf_pp.status = :linkApprovedParty');
                $qb->andWhere($qb->expr()->exists($subParty->getDQL()))
                    ->setParameter('partyOrgId', $partyId)
                    ->setParameter('politicalPartyType', Organization::TYPE_POLITICAL_PARTY)
                    ->setParameter('linkApprovedParty', $approvedLink);
            }
        }

        if ($filters->filterYear && null !== $filters->yearMin && null !== $filters->yearMax) {
            $rangeStart = new \DateTimeImmutable(sprintf('%04d-01-01', $filters->yearMin));
            $rangeEnd = new \DateTimeImmutable(sprintf('%04d-12-31', $filters->yearMax));
            $subYear = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Membership::class, 'sf_my')
                ->where('sf_my.person = p')
                ->andWhere('sf_my.status = :linkApprovedYear')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('sf_my.year'),
                            $qb->expr()->gte('sf_my.year', ':yMin'),
                            $qb->expr()->lte('sf_my.year', ':yMax'),
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('sf_my.year'),
                            $qb->expr()->isNotNull('sf_my.startDate'),
                            $qb->expr()->gte('sf_my.startDate', ':rangeStart'),
                            $qb->expr()->lte('sf_my.startDate', ':rangeEnd'),
                        ),
                    ),
                );
            $qb->andWhere($qb->expr()->exists($subYear->getDQL()))
                ->setParameter('linkApprovedYear', $approvedLink)
                ->setParameter('yMin', $filters->yearMin)
                ->setParameter('yMax', $filters->yearMax)
                ->setParameter('rangeStart', $rangeStart)
                ->setParameter('rangeEnd', $rangeEnd);
        }

        if ($filters->aliveOnly) {
            $qb->andWhere('p.deathDate IS NULL');
        }

        if ($filters->activeOnly) {
            $subActive = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Position::class, 'sf_pa')
                ->where('sf_pa.person = p')
                ->andWhere('sf_pa.endDate IS NULL')
                ->andWhere('sf_pa.status = :linkApprovedActive');
            $qb->andWhere($qb->expr()->exists($subActive->getDQL()))
                ->setParameter('linkApprovedActive', $approvedLink);
        }

        return $qb;
    }

    /**
     * @param list<int> $personIds
     *
     * @return array<int, list<Organization>> jusqu’à 3 organisations d’influence par personne
     */
    public function loadTopInfluenceOrganizationsByPersonIds(array $personIds, int $limitPerPerson = 3): array
    {
        $map = [];
        foreach ($personIds as $id) {
            $map[$id] = [];
        }

        if ([] === $personIds) {
            return $map;
        }

        $types = [
            Organization::TYPE_INFLUENCE_NETWORK,
            Organization::TYPE_THINK_TANK,
            Organization::TYPE_LOBBY_GROUP,
        ];

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(Membership::class, 'm')
            ->innerJoin('m.person', 'mp')
            ->innerJoin('m.organization', 'mo')
            ->where('mp.id IN (:ids)')
            ->andWhere('m.status = :mapproved')
            ->andWhere('mo.type IN (:otypes)')
            ->orderBy('m.year', 'DESC')
            ->setParameter('ids', $personIds)
            ->setParameter('mapproved', 'approved')
            ->setParameter('otypes', $types);

        /** @var list<Membership> $memberships */
        $memberships = $qb->getQuery()->getResult();

        foreach ($memberships as $membership) {
            $person = $membership->getPerson();
            $org = $membership->getOrganization();
            if (null === $person || null === $org) {
                continue;
            }
            $pid = $person->getId();
            if (null === $pid || !\array_key_exists($pid, $map)) {
                continue;
            }
            foreach ($map[$pid] as $existing) {
                if ($existing->getId() === $org->getId()) {
                    continue 2;
                }
            }
            if (\count($map[$pid]) < $limitPerPerson) {
                $map[$pid][] = $org;
            }
        }

        return $map;
    }

    /**
     * @return list<Person>
     */
    public function findApprovedMembersForOrganization(Organization $organization, ?int $year = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createApprovedMembersQueryBuilder($organization, $year)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var list<Person> $res */
        $res = $qb->getQuery()->getResult();

        return $res;
    }

    public function createApprovedMembersQueryBuilder(Organization $organization, ?int $year): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->distinct()
            ->orderBy('p.familyName', 'ASC')
            ->addOrderBy('p.givenName', 'ASC');
        $this->applyApprovedOrganizationMemberConstraints($qb, $organization, $year);

        return $qb;
    }

    public function countApprovedMembersForOrganization(Organization $organization, ?int $year = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)');

        $this->applyApprovedOrganizationMemberConstraints($qb, $organization, $year);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyApprovedOrganizationMemberConstraints(QueryBuilder $qb, Organization $organization, ?int $year): void
    {
        $approvedLink = 'approved';
        $qb->andWhere('p.status = :papproved')
            ->setParameter('papproved', Person::STATUS_APPROVED);

        $subMem = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Membership::class, 'om')
            ->where('om.person = p')
            ->andWhere('om.organization = :memOrg')
            ->andWhere('om.status = :omApproved');
        $subPos = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Position::class, 'op')
            ->where('op.person = p')
            ->andWhere('op.organization = :memOrg')
            ->andWhere('op.status = :omApproved');

        $qb->setParameter('memOrg', $organization)
            ->setParameter('omApproved', $approvedLink)
            ->andWhere($qb->expr()->orX(
                $qb->expr()->exists($subMem->getDQL()),
                $qb->expr()->exists($subPos->getDQL()),
            ));

        if (null !== $year) {
            $yearStart = new \DateTimeImmutable(sprintf('%04d-01-01', $year));
            $yearEnd = new \DateTimeImmutable(sprintf('%04d-12-31', $year));
            $subYearMem = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Membership::class, 'oym')
                ->where('oym.person = p')
                ->andWhere('oym.organization = :memOrg')
                ->andWhere('oym.status = :omApproved')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->eq('oym.year', ':filterYear'),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('oym.year'),
                            $qb->expr()->isNotNull('oym.startDate'),
                            $qb->expr()->gte('oym.startDate', ':yStart'),
                            $qb->expr()->lte('oym.startDate', ':yEnd'),
                        ),
                    ),
                );
            $subYearPos = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(Position::class, 'oyp')
                ->where('oyp.person = p')
                ->andWhere('oyp.organization = :memOrg')
                ->andWhere('oyp.status = :omApproved')
                ->andWhere('oyp.startDate <= :yEnd')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('oyp.endDate'),
                        $qb->expr()->gte('oyp.endDate', ':yStart'),
                    ),
                );
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->exists($subYearMem->getDQL()),
                $qb->expr()->exists($subYearPos->getDQL()),
            ))
                ->setParameter('filterYear', $year)
                ->setParameter('yStart', $yearStart)
                ->setParameter('yEnd', $yearEnd);
        }
    }
}
