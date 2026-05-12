<?php

declare(strict_types=1);

namespace App\Module\Source\Service;

use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class EntitySourceManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function link(Source $source, string $entityType, int $entityId, ?User $addedBy): EntitySource
    {
        $link = $this->persistLink($source, $entityType, $entityId, $addedBy);
        $this->entityManager->flush();

        return $link;
    }

    public function persistLink(Source $source, string $entityType, int $entityId, ?User $addedBy): EntitySource
    {
        $link = new EntitySource();
        $link->setSource($source);
        $link->setEntityType($entityType);
        $link->setEntityId($entityId);
        $link->setAddedBy($addedBy);
        $this->entityManager->persist($link);

        return $link;
    }

    public function unlink(Source $source, string $entityType, int $entityId): void
    {
        $repo = $this->entityManager->getRepository(EntitySource::class);
        $link = $repo->findOneBy([
            'source' => $source,
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
        if (null !== $link) {
            $this->entityManager->remove($link);
            $this->entityManager->flush();
        }
    }
}
