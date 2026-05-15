<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Service\EntitySourceManager;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Fusionne les adhésions « une année » consécutives vers une seule ligne avec startDate / endDate.
 */
final class WikidataMembershipYearConsolidator
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntitySourceManager $entitySourceManager,
    ) {
    }

    /**
     * @param list<array{lo: int, hi: int}> $intervals
     *
     * @return list<array{lo: int, hi: int}>
     */
    public static function mergeAdjacentOrOverlappingYearIntervals(array $intervals): array
    {
        if ([] === $intervals) {
            return [];
        }
        usort($intervals, static fn (array $a, array $b): int => $a['lo'] <=> $b['lo']);
        $out = [];
        $cur = $intervals[0];
        for ($i = 1, $n = \count($intervals); $i < $n; ++$i) {
            $next = $intervals[$i];
            if ($next['lo'] <= $cur['hi'] + 1) {
                $cur['hi'] = max($cur['hi'], $next['hi']);
            } else {
                $out[] = $cur;
                $cur = $next;
            }
        }
        $out[] = $cur;

        return $out;
    }

    public function consolidate(Person $person, Organization $organization, ?User $systemUser = null): void
    {
        $items = [];
        foreach ($person->getMemberships() as $m) {
            if ($m->getOrganization()?->getId() !== $organization->getId()) {
                continue;
            }
            $bounds = $this->yearBounds($m);
            if (null === $bounds) {
                continue;
            }
            $items[] = ['m' => $m, 'lo' => $bounds[0], 'hi' => $bounds[1]];
        }
        if ([] === $items) {
            return;
        }
        usort($items, static fn (array $a, array $b): int => $a['lo'] <=> $b['lo']);
        /** @var list<array{lo: int, hi: int, members: list<Membership>}> $groups */
        $groups = [];
        foreach ($items as $item) {
            if ([] === $groups) {
                $groups[] = ['lo' => $item['lo'], 'hi' => $item['hi'], 'members' => [$item['m']]];

                continue;
            }
            $lastIdx = \count($groups) - 1;
            $last = $groups[$lastIdx];
            if ($item['lo'] <= $last['hi'] + 1) {
                $last['hi'] = max($last['hi'], $item['hi']);
                $last['members'][] = $item['m'];
                $groups[$lastIdx] = $last;
            } else {
                $groups[] = ['lo' => $item['lo'], 'hi' => $item['hi'], 'members' => [$item['m']]];
            }
        }

        foreach ($groups as $group) {
            $members = $group['members'];
            if ([] === $members) {
                continue;
            }
            if (1 === \count($members)) {
                $only = $members[0];
                $this->applyBoundsToMembership($only, $group['lo'], $group['hi']);

                continue;
            }
            $keep = $this->pickCanonicalMembership($members);
            foreach ($members as $m) {
                if ($m->getId() === $keep->getId()) {
                    continue;
                }
                $this->migrateEntitySourcesThenRemoveMembership($m, $keep, $systemUser);
            }
            $this->applyBoundsToMembership($keep, $group['lo'], $group['hi']);
        }
        $this->em()->flush();
    }

    /** @return ?array{0: int, 1: int} [lo, hi] années inclusives */
    private function yearBounds(Membership $m): ?array
    {
        $y = $m->getYear();
        if (null !== $y) {
            return [$y, $y];
        }
        $start = $m->getStartDate();
        $end = $m->getEndDate();
        if ($start instanceof \DateTimeImmutable && $end instanceof \DateTimeImmutable) {
            $lo = (int) $start->format('Y');
            $hi = (int) $end->format('Y');

            return [$lo, $hi];
        }

        return null;
    }

    private function applyBoundsToMembership(Membership $m, int $lo, int $hi): void
    {
        $m->setStartDate(new \DateTimeImmutable($lo.'-01-01'));
        $m->setEndDate(new \DateTimeImmutable($hi.'-12-31'));
        if ($lo === $hi) {
            $m->setYear($lo);
        } else {
            $m->setYear(null);
        }
    }

    /**
     * @param list<Membership> $members
     */
    private function pickCanonicalMembership(array $members): Membership
    {
        $best = $members[0];
        $bestId = $best->getId();
        foreach ($members as $m) {
            $id = $m->getId();
            if (null === $id) {
                continue;
            }
            if (null === $bestId || $id < $bestId) {
                $best = $m;
                $bestId = $id;
            }
        }

        return $best;
    }

    private function migrateEntitySourcesThenRemoveMembership(Membership $from, Membership $keep, ?User $systemUser): void
    {
        $fromId = $from->getId();
        $keepId = $keep->getId();
        if (null !== $fromId && null !== $keepId) {
            $repo = $this->em()->getRepository(EntitySource::class);
            $fromLinks = $repo->findBy([
                'entityType' => EntitySource::ENTITY_MEMBERSHIP,
                'entityId' => $fromId,
            ]);
            foreach ($fromLinks as $link) {
                $source = $link->getSource();
                if (null === $source) {
                    $this->em()->remove($link);

                    continue;
                }
                $dup = $repo->findOneBy([
                    'source' => $source,
                    'entityType' => EntitySource::ENTITY_MEMBERSHIP,
                    'entityId' => $keepId,
                ]);
                if (null === $dup) {
                    $this->entitySourceManager->persistLink($source, EntitySource::ENTITY_MEMBERSHIP, $keepId, $systemUser);
                }
                $this->em()->remove($link);
            }
        }
        $this->em()->remove($from);
    }

    private function em(): EntityManagerInterface
    {
        $m = $this->doctrine->getManager();
        if (!$m instanceof EntityManagerInterface) {
            throw new \LogicException('ORM EntityManager attendu pour WikidataMembershipYearConsolidator.');
        }

        return $m;
    }
}
