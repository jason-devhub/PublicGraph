<?php

declare(strict_types=1);

namespace App\Module\Proximity\Calculator;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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
        $conn->executeStatement('TRUNCATE TABLE person_similarities');

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
        $sql = <<<'SQL'
            SELECT m.organization_id AS oid, m.person_id AS pid, m.year AS m_year
            FROM memberships m
            INNER JOIN persons p ON p.id = m.person_id AND p.deleted_at IS NULL AND p.status = :pp
            INNER JOIN organizations o ON o.id = m.organization_id AND o.status = :op
            WHERE m.status = :ap
            SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'ap' => 'approved',
            'pp' => Person::STATUS_APPROVED,
            'op' => Organization::STATUS_APPROVED,
        ], [
            'ap' => ParameterType::STRING,
            'pp' => ParameterType::STRING,
            'op' => ParameterType::STRING,
        ]);

        /** @var array<int, array<int, list<array{year: ?int}>>> $byOrg */
        $byOrg = [];
        foreach ($rows as $row) {
            $oid = (int) $row['oid'];
            $pid = (int) $row['pid'];
            $yearRaw = $row['m_year'];
            $year = null === $yearRaw || '' === $yearRaw ? null : (int) $yearRaw;
            $byOrg[$oid][$pid][] = ['year' => $year];
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

        $sql = <<<'SQL'
            SELECT m.organization_id AS oid, m.person_id AS pid
            FROM memberships m
            INNER JOIN persons p ON p.id = m.person_id AND p.deleted_at IS NULL AND p.status = :pp
            INNER JOIN organizations o ON o.id = m.organization_id AND o.type = :ptype AND o.status = :op
            WHERE m.status = :ap
            SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'ap' => 'approved',
            'pp' => Person::STATUS_APPROVED,
            'ptype' => Organization::TYPE_POLITICAL_PARTY,
            'op' => Organization::STATUS_APPROVED,
        ], [
            'ap' => ParameterType::STRING,
            'pp' => ParameterType::STRING,
            'ptype' => ParameterType::STRING,
            'op' => ParameterType::STRING,
        ]);

        /** @var array<int, list<int>> $orgToPeople */
        $orgToPeople = [];
        foreach ($rows as $row) {
            $orgToPeople[(int) $row['oid']][] = (int) $row['pid'];
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
        $sql = <<<'SQL'
            SELECT m.organization_id AS oid, m.person_id AS pid, party.european_family AS fam
            FROM memberships m
            INNER JOIN persons p ON p.id = m.person_id AND p.deleted_at IS NULL AND p.status = :pp
            INNER JOIN organizations o ON o.id = m.organization_id AND o.status = :op
            INNER JOIN parties party ON party.organization_id = o.id
                AND party.european_family IS NOT NULL AND party.european_family <> ''
            WHERE m.status = :ap
            SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'ap' => 'approved',
            'pp' => Person::STATUS_APPROVED,
            'op' => Organization::STATUS_APPROVED,
        ], [
            'ap' => ParameterType::STRING,
            'pp' => ParameterType::STRING,
            'op' => ParameterType::STRING,
        ]);

        /** @var array<int, array{people: list<int>, fam: string}> $byOrg */
        $byOrg = [];
        foreach ($rows as $row) {
            $oid = (int) $row['oid'];
            $fam = (string) $row['fam'];
            if (!isset($byOrg[$oid])) {
                $byOrg[$oid] = ['people' => [], 'fam' => $fam];
            }
            $byOrg[$oid]['people'][] = (int) $row['pid'];
        }

        foreach ($byOrg as $bundle) {
            $people = array_values(array_unique($bundle['people']));
            $fam = $bundle['fam'];
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

        $sql = <<<'SQL'
            SELECT pos.organization_id AS oid, o.type AS org_type, pos.person_id AS pid,
                   pos.start_date AS start_date, pos.end_date AS end_date
            FROM positions pos
            INNER JOIN persons p ON p.id = pos.person_id AND p.deleted_at IS NULL AND p.status = :pp
            INNER JOIN organizations o ON o.id = pos.organization_id AND o.status = :op
            WHERE pos.status = :ps AND o.type IN (:otypes)
            SQL;

        $rows = $this->entityManager->getConnection()->fetchAllAssociative($sql, [
            'ps' => 'approved',
            'pp' => Person::STATUS_APPROVED,
            'op' => Organization::STATUS_APPROVED,
            'otypes' => $types,
        ], [
            'ps' => ParameterType::STRING,
            'pp' => ParameterType::STRING,
            'op' => ParameterType::STRING,
            'otypes' => ArrayParameterType::STRING,
        ]);

        /** @var array<int, list<array{person: int, start: \DateTimeImmutable, end: ?\DateTimeImmutable, org_type: string}>> $byOrg */
        $byOrg = [];
        foreach ($rows as $row) {
            $oid = (int) $row['oid'];
            $byOrg[$oid][] = [
                'person' => (int) $row['pid'],
                'start' => $this->coerceDateTimeImmutable($row['start_date']),
                'end' => $this->coerceOptionalDateTimeImmutable($row['end_date']),
                'org_type' => (string) $row['org_type'],
            ];
        }

        foreach ($byOrg as $oid => $list) {
            $orgType = $list[0]['org_type'] ?? Organization::TYPE_GOVERNMENT_BODY;
            $weight = Organization::TYPE_INTERNATIONAL_BODY === $orgType ? $wInt : $wLeg;

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
        static $farFuture = null;
        if (null === $farFuture) {
            $farFuture = new \DateTimeImmutable('+100 years');
        }
        $e1eff = $e1 ?? $farFuture;
        $e2eff = $e2 ?? $farFuture;

        return $s1 <= $e2eff && $s2 <= $e1eff;
    }

    private function coerceDateTimeImmutable(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        if (!\is_string($value)) {
            throw new \UnexpectedValueException('Date attendue depuis la base (chaîne ou DateTimeInterface).');
        }

        return new \DateTimeImmutable($value);
    }

    private function coerceOptionalDateTimeImmutable(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return $this->coerceDateTimeImmutable($value);
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
