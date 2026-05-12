<?php

declare(strict_types=1);

namespace App\Module\Source\Service;

use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\EntitySourceRepository;

final class SourceFinder
{
    public function __construct(
        private readonly EntitySourceRepository $entitySourceRepository,
    ) {
    }

    /** @return list<Source> */
    public function findFor(string $entityType, int $entityId): array
    {
        $links = $this->entitySourceRepository->findBy(
            ['entityType' => $entityType, 'entityId' => $entityId],
            ['createdAt' => 'ASC'],
        );

        $sources = [];
        foreach ($links as $link) {
            $src = $link->getSource();
            if (null !== $src) {
                $sources[] = $src;
            }
        }

        return $sources;
    }
}
