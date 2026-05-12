<?php

declare(strict_types=1);

namespace App\Module\Proximity\Service;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Source\Repository\EntitySourceRepository;

/**
 * Co-présences : même organisation, même année de membership (statut approuvé).
 *
 * @phpstan-type CoPresenceRow array{
 *   organization: Organization,
 *   year: int,
 *   persons: list<Person>,
 *   sources: list<\App\Module\Source\Entity\Source>
 * }
 */
final class CoPresenceFinder
{
    public function __construct(
        private readonly EntitySourceRepository $entitySourceRepository,
    ) {
    }

    /**
     * @return list<CoPresenceRow>
     */
    public function findFor(Person $person): array
    {
        $out = [];
        $approved = $person->getMemberships()->filter(
            static fn (Membership $m): bool => 'approved' === $m->getStatus(),
        );
        foreach ($approved as $membership) {
            $org = $membership->getOrganization();
            $year = $membership->getYear();
            if (!$org instanceof Organization || null === $year) {
                continue;
            }
            $others = [];
            foreach ($org->getMemberships() as $m2) {
                if ('approved' !== $m2->getStatus()) {
                    continue;
                }
                $p2 = $m2->getPerson();
                if (!$p2 instanceof Person || $p2->getId() === $person->getId()) {
                    continue;
                }
                if (Person::STATUS_APPROVED !== $p2->getStatus()) {
                    continue;
                }
                if ($m2->getYear() !== $year) {
                    continue;
                }
                $others[] = $p2;
            }
            if ([] === $others) {
                continue;
            }
            $sources = $this->entitySourceRepository->findDistinctSourcesLinkedToOrganization($org);
            $out[] = [
                'organization' => $org,
                'year' => $year,
                'persons' => array_values($others),
                'sources' => $sources,
            ];
        }

        return $out;
    }
}
