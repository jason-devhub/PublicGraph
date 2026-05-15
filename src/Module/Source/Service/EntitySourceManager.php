<?php

declare(strict_types=1);

namespace App\Module\Source\Service;

use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class EntitySourceManager
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function link(Source $source, string $entityType, int $entityId, ?User $addedBy): EntitySource
    {
        $link = $this->persistLink($source, $entityType, $entityId, $addedBy);
        $this->em()->flush();

        return $link;
    }

    public function persistLink(Source $source, string $entityType, int $entityId, ?User $addedBy): EntitySource
    {
        $link = new EntitySource();
        $link->setSource($source);
        $link->setEntityType($entityType);
        $link->setEntityId($entityId);
        $link->setAddedBy($addedBy);
        $this->em()->persist($link);

        return $link;
    }

    public function unlink(Source $source, string $entityType, int $entityId): void
    {
        $repo = $this->em()->getRepository(EntitySource::class);
        $link = $repo->findOneBy([
            'source' => $source,
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
        if (null !== $link) {
            $this->em()->remove($link);
            $this->em()->flush();
        }
    }

    private function em(): EntityManagerInterface
    {
        $m = $this->doctrine->getManager();
        if (!$m instanceof EntityManagerInterface) {
            throw new \LogicException('ORM EntityManager attendu pour EntitySourceManager.');
        }

        return $m;
    }
}
