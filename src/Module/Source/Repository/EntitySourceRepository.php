<?php

declare(strict_types=1);

namespace App\Module\Source\Repository;

use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntitySource>
 */
class EntitySourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntitySource::class);
    }

    public function countFor(string $entityType, int $entityId): int
    {
        return (int) $this->createQueryBuilder('es')
            ->select('COUNT(es.id)')
            ->andWhere('es.entityType = :t')
            ->andWhere('es.entityId = :id')
            ->setParameter('t', $entityType)
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sources liées aux appartenances, mandats et portes tournantes d’une personne.
     *
     * @return list<Source>
     */
    public function findDistinctSourcesLinkedToPerson(Person $person): array
    {
        if (null === $person->getId()) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT s')
            ->from(Source::class, 's')
            ->innerJoin('s.entitySources', 'es')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        'es.entityType = :tMem',
                        'es.entityId IN (SELECT mm.id FROM '.Membership::class.' mm WHERE mm.person = :person)',
                    ),
                    $qb->expr()->andX(
                        'es.entityType = :tPos',
                        'es.entityId IN (SELECT pp.id FROM '.Position::class.' pp WHERE pp.person = :person)',
                    ),
                    $qb->expr()->andX(
                        'es.entityType = :tRd',
                        'es.entityId IN (SELECT rd.id FROM '.RevolvingDoor::class.' rd WHERE rd.person = :person)',
                    ),
                ),
            )
            ->setParameter('person', $person)
            ->setParameter('tMem', EntitySource::ENTITY_MEMBERSHIP)
            ->setParameter('tPos', EntitySource::ENTITY_POSITION)
            ->setParameter('tRd', EntitySource::ENTITY_REVOLVING_DOOR);

        /** @var list<Source> $out */
        $out = $qb->getQuery()->getResult();

        return $out;
    }

    /**
     * @return list<Source>
     */
    public function findDistinctSourcesLinkedToOrganization(Organization $organization): array
    {
        if (null === $organization->getId()) {
            return [];
        }
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT s')
            ->from(Source::class, 's')
            ->innerJoin('s.entitySources', 'es')
            ->andWhere('es.entityType = :tMem')
            ->andWhere('es.entityId IN (SELECT mm.id FROM '.Membership::class.' mm WHERE mm.organization = :org)')
            ->setParameter('org', $organization)
            ->setParameter('tMem', EntitySource::ENTITY_MEMBERSHIP);

        /** @var list<Source> $out */
        $out = $qb->getQuery()->getResult();

        return $out;
    }
}
