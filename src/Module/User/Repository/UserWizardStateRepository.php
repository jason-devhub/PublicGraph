<?php

declare(strict_types=1);

namespace App\Module\User\Repository;

use App\Module\User\Entity\User;
use App\Module\User\Entity\UserWizardState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserWizardState>
 */
final class UserWizardStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWizardState::class);
    }

    public function getOrCreate(User $user, string $wizardType): UserWizardState
    {
        $existing = $this->findOneBy(['user' => $user, 'wizardType' => $wizardType]);
        if ($existing instanceof UserWizardState) {
            return $existing;
        }

        $state = new UserWizardState();
        $state->setUser($user);
        $state->setWizardType($wizardType);
        $this->getEntityManager()->persist($state);

        return $state;
    }

    public function save(UserWizardState $state, bool $flush = false): void
    {
        $this->getEntityManager()->persist($state);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
