<?php

declare(strict_types=1);

namespace App\Module\Proximity\Calculator;

use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\Party;
use App\Module\Person\Entity\Person;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ProximityCalculator
{
    /** @param array{weights: array<string, float>, min_score_to_store: float, top_n_per_person: int} $proximityConfig */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PersonSimilarityRepository $personSimilarityRepository,
        #[Autowire(param: 'public_graph.proximity')]
        private readonly array $proximityConfig,
    ) {
    }

    /**
     * @return array{pairs_stored: int, persons: int}
     */
    public function calculateForAll(): array
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM person_similarities');

        $weights = $this->proximityConfig['weights'];
        $minScore = (float) $this->proximityConfig['min_score_to_store'];
        $topN = (int) $this->proximityConfig['top_n_per_person'];

        $pairScore = [];
        $pairDetails = [];

        $this->accumulateMembershipScores($pairScore, $pairDetails, $weights);
        $this->accumulatePartyScores($pairScore, $pairDetails, $weights);
        $this->accumulatePositionOverlapScores($pairScore, $pairDetails, $weights);

        $neighbors = [];
        foreach ($pairScore as $key => $score) {
            if ($score < $minScore) {
                continue;
            }
            [$a, $b] = explode('-', $key, 2);
            $ai = (int) $a;
            $bi = (int) $b;
            $neighbors[$ai][$bi] = max($neighbors[$ai][$bi] ?? 0.0, $score);
            $neighbors[$bi][$ai] = max($neighbors[$bi][$ai] ?? 0.0, $score);
        }

        $keptUndirected = [];
        foreach ($neighbors as $pid => $others) {
            arsort($others);
            $slice = \array_slice($others, 0, $topN, true);
            foreach ($slice as $oid => $sc) {
                $p1 = min($pid, (int) $oid);
                $p2 = max($pid, (int) $oid);
                $k = $p1.'-'.$p2;
                $keptUndirected[$k] = max($keptUndirected[$k] ?? 0.0, $sc);
            }
        }

        $rows = [];
        foreach ($keptUndirected as $key => $score) {
            [$a, $b] = array_map('intval', explode('-', $key, 2));
            if ($score < $minScore) {
                continue;
            }
            $details = $this->finalizeDetails($pairDetails[$key] ?? null);
            $rows[] = ['personAId' => $a, 'personBId' => $b, 'score' => number_format($score, 2, '.', ''), 'details' => $details];
            $rows[] = ['personAId' => $b, 'personBId' => $a, 'score' => number_format($score, 2, '.', ''), 'details' => $details];
        }

        $this->personSimilarityRepository->bulkInsert($rows);

        return ['pairs_stored' => \count($rows), 'persons' => \count($neighbors)];
    }

    /**
     * Recalcul global (simple et correct). À affiner pour de très grandes bases.
     */
    public function calculateForPerson(Person $person): void
    {
        $this->calculateForAll();
    }

    /**
     * @param array<string, float>                $weights
     * @param array<string, float>                $pairScore
     * @param array<string, array<string, mixed>> $pairDetails
     */
    private function accumulateMembershipScores(array &$pairScore, array &$pairDetails, array $weights): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(Membership::class, 'm')
            ->innerJoin('m.person', 'p')
            ->innerJoin('m.organization', 'o')
            ->andWhere('m.status = :ap')
            ->andWhere('p.status = :pp')
            ->andWhere('o.status = :op')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('ap', 'approved')
            ->setParameter('pp', Person::STATUS_APPROVED)
            ->setParameter('op', Organization::STATUS_APPROVED);

        /** @var list<Membership> $memberships */
        $memberships = $qb->getQuery()->getResult();

        /** @var array<int, array<int, list<array{year: ?int}>>> $byOrg */
        $byOrg = [];
        foreach ($memberships as $m) {
            $p = $m->getPerson();
            $o = $m->getOrganization();
            if (null === $p?->getId() || null === $o?->getId()) {
                continue;
            }
            $oid = (int) $o->getId();
            $pid = (int) $p->getId();
            $byOrg[$oid][$pid][] = ['year' => $m->getYear()];
        }

        $wYear = (float) ($weights['same_event_same_year'] ?? 3.0);
        $wOrg = (float) ($weights['same_organization'] ?? 1.0);

        foreach ($byOrg as $oid => $personYears) {
            $pids = array_keys($personYears);
            $n = \count($pids);
            for ($i = 0; $i < $n; ++$i) {
                for ($j = $i + 1; $j < $n; ++$j) {
                    $a = $pids[$i];
                    $b = $pids[$j];
                    $key = $this->pairKey($a, $b);
                    $add = 0.0;
                    $events = 0;
                    $generic = 0;
                    foreach ($personYears[$a] as $ya) {
                        foreach ($personYears[$b] as $yb) {
                            $y1 = $ya['year'];
                            $y2 = $yb['year'];
                            if (null !== $y1 && null !== $y2 && $y1 === $y2) {
                                $add += $wYear;
                                ++$events;
                            } else {
                                $add += $wOrg;
                                ++$generic;
                            }
                        }
                    }
                    if ($add <= 0) {
                        continue;
                    }
                    $pairScore[$key] = ($pairScore[$key] ?? 0.0) + $add;
                    $this->mergeDetails($pairDetails, $key, [
                        'same_organization_memberships' => $generic,
                        'same_year_events' => $events,
                        'organization_id' => $oid,
                    ]);
                }
            }
        }
    }

    /**
     * @param array<string, float>                $weights
     * @param array<string, float>                $pairScore
     * @param array<string, array<string, mixed>> $pairDetails
     */
    private function accumulatePartyScores(array &$pairScore, array &$pairDetails, array $weights): void
    {
        $w = (float) ($weights['same_party'] ?? 1.0);
        $wEfam = (float) ($weights['same_european_family'] ?? 0.5);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(Membership::class, 'm')
            ->innerJoin('m.person', 'p')
            ->innerJoin('m.organization', 'o')
            ->andWhere('m.status = :ap')
            ->andWhere('p.status = :pp')
            ->andWhere('o.type = :ptype')
            ->andWhere('o.status = :op')
            ->setParameter('ap', 'approved')
            ->setParameter('pp', Person::STATUS_APPROVED)
            ->setParameter('ptype', Organization::TYPE_POLITICAL_PARTY)
            ->setParameter('op', Organization::STATUS_APPROVED);

        /** @var list<Membership> $memberships */
        $memberships = $qb->getQuery()->getResult();

        /** @var array<int, list<int>> $orgToPeople */
        $orgToPeople = [];
        foreach ($memberships as $m) {
            $p = $m->getPerson();
            $o = $m->getOrganization();
            if (null === $p?->getId() || null === $o?->getId()) {
                continue;
            }
            $orgToPeople[(int) $o->getId()][] = (int) $p->getId();
        }

        foreach ($orgToPeople as $people) {
            $people = array_values(array_unique($people));
            $n = \count($people);
            for ($i = 0; $i < $n; ++$i) {
                for ($j = $i + 1; $j < $n; ++$j) {
                    $key = $this->pairKey($people[$i], $people[$j]);
                    $pairScore[$key] = ($pairScore[$key] ?? 0.0) + $w;
                    $this->mergeDetails($pairDetails, $key, ['shared_political_party' => 1]);
                }
            }
        }

        $this->accumulateEuropeanFamilyScores($pairScore, $pairDetails, $wEfam);
    }

    /**
     * @param array<string, float>                $pairScore
     * @param array<string, array<string, mixed>> $pairDetails
     */
    private function accumulateEuropeanFamilyScores(array &$pairScore, array &$pairDetails, float $wEfam): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('party')
            ->from(Party::class, 'party')
            ->innerJoin('party.organization', 'o')
            ->andWhere('party.europeanFamily IS NOT NULL')
            ->andWhere('o.status = :op')
            ->setParameter('op', Organization::STATUS_APPROVED);

        /** @var list<Party> $parties */
        $parties = $qb->getQuery()->getResult();

        foreach ($parties as $party) {
            $org = $party->getOrganization();
            $fam = $party->getEuropeanFamily();
            if (null === $org || null === $fam || '' === $fam) {
                continue;
            }
            $people = [];
            foreach ($org->getMemberships() as $m) {
                if ('approved' !== $m->getStatus()) {
                    continue;
                }
                $p = $m->getPerson();
                if ($p instanceof Person && Person::STATUS_APPROVED === $p->getStatus() && null !== $p->getId()) {
                    $people[] = (int) $p->getId();
                }
            }
            $people = array_values(array_unique($people));
            $n = \count($people);
            for ($i = 0; $i < $n; ++$i) {
                for ($j = $i + 1; $j < $n; ++$j) {
                    $key = $this->pairKey($people[$i], $people[$j]);
                    $pairScore[$key] = ($pairScore[$key] ?? 0.0) + $wEfam;
                    $this->mergeDetails($pairDetails, $key, ['same_european_family' => 1, 'family' => $fam]);
                }
            }
        }
    }

    /**
     * @param array<string, float>                $weights
     * @param array<string, float>                $pairScore
     * @param array<string, array<string, mixed>> $pairDetails
     */
    private function accumulatePositionOverlapScores(array &$pairScore, array &$pairDetails, array $weights): void
    {
        $wLeg = (float) ($weights['same_legislative_body_overlap'] ?? 0.5);
        $wInt = (float) ($weights['same_international_body_overlap'] ?? 2.0);

        $types = [Organization::TYPE_GOVERNMENT_BODY, Organization::TYPE_INTERNATIONAL_BODY];

        $qb = $this->entityManager->createQueryBuilder()
            ->select('pos')
            ->from(Position::class, 'pos')
            ->innerJoin('pos.person', 'p')
            ->innerJoin('pos.organization', 'o')
            ->andWhere('pos.status = :ps')
            ->andWhere('p.status = :pp')
            ->andWhere('o.status = :op')
            ->andWhere('o.type IN (:otypes)')
            ->setParameter('ps', 'approved')
            ->setParameter('pp', Person::STATUS_APPROVED)
            ->setParameter('op', Organization::STATUS_APPROVED)
            ->setParameter('otypes', $types);

        /** @var list<Position> $positions */
        $positions = $qb->getQuery()->getResult();

        /** @var array<int, list<array{person: int, start: \DateTimeImmutable, end: ?\DateTimeImmutable}>> $byOrg */
        $byOrg = [];
        foreach ($positions as $pos) {
            $p = $pos->getPerson();
            $o = $pos->getOrganization();
            if (null === $p?->getId() || null === $o?->getId()) {
                continue;
            }
            $oid = (int) $o->getId();
            $byOrg[$oid][] = [
                'person' => (int) $p->getId(),
                'start' => $pos->getStartDate(),
                'end' => $pos->getEndDate(),
            ];
        }

        foreach ($byOrg as $oid => $list) {
            $o = $this->entityManager->find(Organization::class, $oid);
            $weight = Organization::TYPE_INTERNATIONAL_BODY === $o?->getType() ? $wInt : $wLeg;

            $n = \count($list);
            for ($i = 0; $i < $n; ++$i) {
                for ($j = $i + 1; $j < $n; ++$j) {
                    $a = $list[$i];
                    $b = $list[$j];
                    if ($a['person'] === $b['person']) {
                        continue;
                    }
                    if (!$this->rangesOverlap($a['start'], $a['end'], $b['start'], $b['end'])) {
                        continue;
                    }
                    $key = $this->pairKey($a['person'], $b['person']);
                    $pairScore[$key] = ($pairScore[$key] ?? 0.0) + $weight;
                    $this->mergeDetails($pairDetails, $key, ['position_overlap_org' => $oid]);
                }
            }
        }
    }

    private function rangesOverlap(
        \DateTimeImmutable $s1,
        ?\DateTimeImmutable $e1,
        \DateTimeImmutable $s2,
        ?\DateTimeImmutable $e2,
    ): bool {
        $e1eff = $e1 ?? new \DateTimeImmutable('+100 years');
        $e2eff = $e2 ?? new \DateTimeImmutable('+100 years');

        return $s1 <= $e2eff && $s2 <= $e1eff;
    }

    private function pairKey(int $a, int $b): string
    {
        return $a < $b ? $a.'-'.$b : $b.'-'.$a;
    }

    private function finalizeDetails(?array $raw): array
    {
        $fragments = $raw['fragments'] ?? [];
        $summary = [];
        foreach ($fragments as $f) {
            foreach ($f as $k => $v) {
                if (\is_int($v)) {
                    $summary[$k] = ($summary[$k] ?? 0) + $v;
                } elseif (\is_string($v)) {
                    $summary[$k] = $v;
                }
            }
        }

        return ['summary' => $summary, 'fragments' => $fragments];
    }

    private function mergeDetails(array &$pairDetails, string $key, array $fragment): void
    {
        if (!isset($pairDetails[$key])) {
            $pairDetails[$key] = ['fragments' => []];
        }
        $pairDetails[$key]['fragments'][] = $fragment;
    }
}
