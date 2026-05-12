<?php

declare(strict_types=1);

namespace App\Shared\Service;

use App\Module\User\Entity\User;
use App\Shared\Entity\Revision;
use Doctrine\ORM\EntityManagerInterface;

final class RevisionLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}> $diff
     */
    public function log(string $entityType, int $entityId, array $diff, User $proposed, User $validated, bool $flush = true): void
    {
        foreach ($diff as $field => $change) {
            $revision = new Revision(
                $entityType,
                $entityId,
                (string) $field,
                $change['old'] ?? null,
                $change['new'] ?? null,
                $proposed,
                $validated,
            );
            $this->entityManager->persist($revision);
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
