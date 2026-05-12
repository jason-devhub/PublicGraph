<?php

declare(strict_types=1);

namespace App\Module\Moderation\Repository;

use App\Module\Moderation\Entity\ChangeProposal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChangeProposal>
 */
final class ChangeProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangeProposal::class);
    }

    /** @return list<ChangeProposal> */
    public function findPendingFifo(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :p')->setParameter('p', ChangeProposal::STATUS_PENDING)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
