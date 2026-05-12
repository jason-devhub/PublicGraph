<?php

declare(strict_types=1);

namespace App\Module\Legislation\Repository;

use App\Module\Legislation\Entity\LegislativeAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegislativeAction>
 */
class LegislativeActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegislativeAction::class);
    }
}
