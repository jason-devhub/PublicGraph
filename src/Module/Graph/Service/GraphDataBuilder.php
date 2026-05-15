<?php

declare(strict_types=1);

namespace App\Module\Graph\Service;

use App\Module\Graph\Model\GraphQueryParams;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Shared\I18n\LocalizedContentResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GraphDataBuilder
{
    /** Remplissage des nœuds personne (graphe global) : gris unique, hors palette catégorie. */
    private const string PERSON_NODE_FILL = '#6F7A8C';

    /** Nombre max de nœuds organisation affiliés (mini-graphes personne / organisation, filtre organisation). */
    private const int MAX_AFFILIATED_ORGANIZATIONS = 72;

    /** Graphe global sans filtre org : plus d’organisations pour refléter les liens adhésions / mandats sur le sous-ensemble personnes. */
    private const int MAX_GLOBAL_GRAPH_ORGANIZATIONS = 500;

    /** Personnes supplémentaires liées aux organisations déjà présentes sur le graphe (co-membres / collègues). */
    private const int MAX_CO_MEMBERS_THROUGH_DISPLAYED_ORGS = 80;

    /** Mini-graphe fiche personne (sans filtre organisation) : similarité + co-membres des orgs du centre uniquement. */
    private const int PERSON_EGO_MAX_SIMILAR_NEIGHBORS = 40;

    private const int PERSON_EGO_MAX_CO_ORG_MEMBERS = 120;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PersonRepository $personRepository,
        private readonly CacheInterface $cache,
        private readonly LocalizedContentResolver $localizedContentResolver,
    ) {
    }

    /**
     * @return array{elements: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}}
     */
    public function build(GraphQueryParams $params): array
    {
        $key = 'graph_data_'.$params->locale.'_'.hash('sha256', serialize($params));

        return $this->cache->get($key, function (ItemInterface $item) use ($params) {
            $item->expiresAfter(300);

            return $this->buildUncached($params);
        });
    }

    /**
     * Graphe centré sur une personne : similarités + organisations approuvées + co-membres de ces orgs uniquement
     * (pas le sous-graphe global `maxNodes`).
     *
     * @return array{elements: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}}
     */
    private function buildPersonProfileEgoGraph(GraphQueryParams $params, string $focusSlugTrim): array
    {
        $person = $this->personRepository->findBySlug($focusSlugTrim);
        if (!$person instanceof Person || Person::STATUS_APPROVED !== $person->getStatus() || null !== $person->getDeletedAt()) {
            return ['elements' => ['nodes' => [], 'edges' => []]];
        }

        $centerId = (int) $person->getId();
        $conn = $this->entityManager->getConnection();
        $approved = Organization::STATUS_APPROVED;
        $personApproved = Person::STATUS_APPROVED;

        $orgIds = $this->fetchApprovedOrganizationIdsForPerson($conn, $centerId, $approved, $personApproved);
        sort($orgIds);

        $similarScores = $this->fetchSimilarPersonScoresForCenter($conn, $centerId, self::PERSON_EGO_MAX_SIMILAR_NEIGHBORS);
        $similarOtherIds = array_keys($similarScores);

        $coMemberCap = min(self::PERSON_EGO_MAX_CO_ORG_MEMBERS, max(40, $params->maxNodes));
        $coOrgMemberIds = [] !== $orgIds
            ? $this->fetchCoMemberPersonIdsForOrganizations($conn, $orgIds, $centerId, $approved, $personApproved, $coMemberCap)
            : [];

        $personIdSet = [$centerId => true];
        foreach ($similarOtherIds as $sid) {
            $personIdSet[$sid] = true;
        }
        foreach ($coOrgMemberIds as $cid) {
            $personIdSet[$cid] = true;
        }

        $allPersonIds = array_keys($personIdSet);
        sort($allPersonIds);

        /** @var list<Person> $personEntities */
        $personEntities = $this->personRepository->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->andWhere('p.status = :st')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('ids', $allPersonIds)
            ->setParameter('st', Person::STATUS_APPROVED)
            ->getQuery()
            ->getResult();

        $personById = [];
        foreach ($personEntities as $pe) {
            if (null !== $pe->getId()) {
                $personById[(int) $pe->getId()] = $pe;
            }
        }

        $nodes = [];
        if (isset($personById[$centerId])) {
            $nodes[] = $this->buildPersonGraphNode($personById[$centerId], $focusSlugTrim);
        }
        foreach ($allPersonIds as $pid) {
            if ($pid === $centerId) {
                continue;
            }
            $pe = $personById[$pid] ?? null;
            if ($pe instanceof Person) {
                $nodes[] = $this->buildPersonGraphNode($pe, $focusSlugTrim);
            }
        }

        if ([] !== $orgIds) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('o')
                ->from(Organization::class, 'o')
                ->andWhere('o.id IN (:ids)')
                ->andWhere('o.status = :ost')
                ->setParameter('ids', $orgIds)
                ->setParameter('ost', Organization::STATUS_APPROVED);
            /** @var list<Organization> $orgEntities */
            $orgEntities = $qb->getQuery()->getResult();
            foreach ($orgEntities as $org) {
                $oid = (int) $org->getId();
                $orgType = $org->getType();
                $nodes[] = [
                    'data' => [
                        'id' => 'org-'.$oid,
                        'label' => $this->localizedContentResolver->resolveOrganizationDisplayName($org, $params->locale),
                        'type' => 'organization',
                        'slug' => $org->getSlug(),
                        'orgType' => $orgType,
                        'bgColor' => $this->organizationBgColor($orgType),
                    ],
                ];
            }
        }

        /** @var list<array<string, mixed>> $edges */
        $edges = [];
        $edgeIds = [];

        foreach ($similarScores as $otherId => $score) {
            if ($otherId === $centerId || !isset($personById[$otherId])) {
                continue;
            }
            $a = min($centerId, $otherId);
            $b = max($centerId, $otherId);
            $eid = 'e-'.$a.'-'.$b;
            if (isset($edgeIds[$eid])) {
                continue;
            }
            $edgeIds[$eid] = true;
            $edges[] = [
                'data' => [
                    'id' => $eid,
                    'source' => 'person-'.$a,
                    'target' => 'person-'.$b,
                    'weight' => $score,
                ],
            ];
        }

        if ([] !== $orgIds) {
            $this->appendPersonOrgEdgesForPersonsAndOrgs(
                $conn,
                $edges,
                $allPersonIds,
                $orgIds,
                $approved,
                array_fill_keys($orgIds, true),
            );
        }

        return [
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function fetchApprovedOrganizationIdsForPerson(Connection $conn, int $personId, string $orgApproved, string $personApproved): array
    {
        $sql = 'SELECT DISTINCT x.organization_id FROM ('
            .'SELECT m.organization_id FROM memberships m '
            .'INNER JOIN organizations o ON o.id = m.organization_id '
            .'INNER JOIN persons p ON p.id = m.person_id '
            .'WHERE m.person_id = ? AND m.status = ? AND o.status = ? AND p.status = ? AND p.deleted_at IS NULL '
            .'UNION '
            .'SELECT pos.organization_id FROM positions pos '
            .'INNER JOIN organizations o2 ON o2.id = pos.organization_id '
            .'INNER JOIN persons p2 ON p2.id = pos.person_id '
            .'WHERE pos.person_id = ? AND pos.status = ? AND o2.status = ? AND p2.status = ? AND p2.deleted_at IS NULL'
            .') x';
        $rows = $conn->fetchFirstColumn($sql, [
            $personId, $orgApproved, $orgApproved, $personApproved,
            $personId, $orgApproved, $orgApproved, $personApproved,
        ]);

        $out = [];
        foreach ($rows as $v) {
            $oid = (int) $v;
            if ($oid > 0) {
                $out[$oid] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * @return array<int, float> autre personne => score (tri décroissant implicite)
     */
    private function fetchSimilarPersonScoresForCenter(Connection $conn, int $centerId, int $limit): array
    {
        $rows = $conn->fetchAllAssociative(
            'SELECT person_a_id, person_b_id, score FROM person_similarities '
            .'WHERE person_a_id = ? OR person_b_id = ? ORDER BY score DESC',
            [$centerId, $centerId],
        );

        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $a = (int) $row['person_a_id'];
            $b = (int) $row['person_b_id'];
            $other = $a === $centerId ? $b : $a;
            if ($other === $centerId || isset($seen[$other])) {
                continue;
            }
            $seen[$other] = true;
            $out[$other] = (float) $row['score'];
            if (\count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param list<int> $orgIds
     *
     * @return list<int>
     */
    private function fetchCoMemberPersonIdsForOrganizations(
        Connection $conn,
        array $orgIds,
        int $excludePersonId,
        string $orgApproved,
        string $personApproved,
        int $limit,
    ): array {
        $orgPh = implode(',', array_fill(0, \count($orgIds), '?'));
        $sql = 'SELECT DISTINCT t.person_id FROM ('
            .'SELECT m.person_id FROM memberships m '
            .'INNER JOIN persons p ON p.id = m.person_id '
            .'WHERE m.organization_id IN ('.$orgPh.') AND m.person_id != ? AND m.status = ? '
            .'AND p.status = ? AND p.deleted_at IS NULL '
            .'UNION '
            .'SELECT pos.person_id FROM positions pos '
            .'INNER JOIN persons p2 ON p2.id = pos.person_id '
            .'WHERE pos.organization_id IN ('.$orgPh.') AND pos.person_id != ? AND pos.status = ? '
            .'AND p2.status = ? AND p2.deleted_at IS NULL'
            .') t ORDER BY t.person_id ASC LIMIT '.$limit;

        $params = array_merge(
            $orgIds,
            [$excludePersonId, $orgApproved, $personApproved],
            $orgIds,
            [$excludePersonId, $orgApproved, $personApproved],
        );

        /** @var list<int|string> $col */
        $col = $conn->fetchFirstColumn($sql, $params);
        $out = [];
        foreach ($col as $v) {
            $out[] = (int) $v;
        }

        return $out;
    }

    /**
     * @return array{elements: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}}
     */
    private function buildUncached(GraphQueryParams $params): array
    {
        $orgSlugTrim = \is_string($params->organizationSlug) ? trim($params->organizationSlug) : '';
        $focusTrim = \is_string($params->focusPersonSlug) ? trim($params->focusPersonSlug) : '';
        if ('' !== $focusTrim && '' === $orgSlugTrim) {
            return $this->buildPersonProfileEgoGraph($params, $focusTrim);
        }

        $qb = $this->personRepository->createQueryBuilder('p')
            ->select('p')
            ->distinct()
            ->andWhere('p.status = :ap')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('ap', Person::STATUS_APPROVED)
            ->orderBy('p.id', 'ASC');

        if ([] !== $params->countryIsoCodes) {
            $sub = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(\App\Module\Catalog\Entity\Country::class, 'c')
                ->innerJoin('c.persons', 'cp')
                ->where('cp = p')
                ->andWhere('c.isoCode IN (:codes)');
            $qb->andWhere($qb->expr()->exists($sub->getDQL()))
                ->setParameter('codes', $params->countryIsoCodes);
        }

        if ([] !== $params->roleCategories) {
            $orX = $qb->expr()->orX();
            foreach ($params->roleCategories as $i => $cat) {
                $param = 'rcg_'.$i;
                $orX->add($qb->expr()->like('p.roleCategories', ':'.$param));
                $qb->setParameter($param, '%'.$cat.'%');
            }
            $qb->andWhere($orX);
        }

        if (null !== $params->organizationSlug && '' !== trim($params->organizationSlug)) {
            $org = $this->entityManager->getRepository(Organization::class)->findOneBy(['slug' => trim($params->organizationSlug)]);
            if ($org instanceof Organization && Organization::STATUS_APPROVED === $org->getStatus()) {
                $subM = $this->entityManager->createQueryBuilder()
                    ->select('1')
                    ->from(Membership::class, 'gm')
                    ->where('gm.person = p')
                    ->andWhere('gm.organization = :gorg')
                    ->andWhere('gm.status = :gapp');
                $subP = $this->entityManager->createQueryBuilder()
                    ->select('1')
                    ->from(Position::class, 'gp')
                    ->where('gp.person = p')
                    ->andWhere('gp.organization = :gorg')
                    ->andWhere('gp.status = :gapp');
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->exists($subM->getDQL()),
                    $qb->expr()->exists($subP->getDQL()),
                ))
                    ->setParameter('gorg', $org)
                    ->setParameter('gapp', 'approved');
            }
        }

        if (null !== $params->yearMin && null !== $params->yearMax) {
            $yMin = $params->yearMin;
            $yMax = $params->yearMax;
            $subY = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(Membership::class, 'ym')
                ->where('ym.person = p')
                ->andWhere('ym.status = :yapp')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('ym.year'),
                            $qb->expr()->gte('ym.year', ':ymin'),
                            $qb->expr()->lte('ym.year', ':ymax'),
                        ),
                    ),
                );
            $qb->andWhere($qb->expr()->exists($subY->getDQL()))
                ->setParameter('yapp', 'approved')
                ->setParameter('ymin', $yMin)
                ->setParameter('ymax', $yMax);
        }

        $qb->setMaxResults($params->maxNodes);
        /** @var list<Person> $persons */
        $persons = $qb->getQuery()->getResult();

        /* Même sous-ensemble que le graphe global (tri id, maxNodes), avec la personne fiche toujours incluse. */
        if (\is_string($params->focusPersonSlug) && '' !== trim($params->focusPersonSlug)) {
            $slug = trim($params->focusPersonSlug);
            $fp = $this->personRepository->findBySlug($slug);
            if ($fp instanceof Person && Person::STATUS_APPROVED === $fp->getStatus() && null === $fp->getDeletedAt()) {
                $fid = (int) $fp->getId();
                $present = false;
                foreach ($persons as $p) {
                    if ((int) $p->getId() === $fid) {
                        $present = true;
                        break;
                    }
                }
                if (!$present) {
                    if (\count($persons) >= $params->maxNodes) {
                        array_pop($persons);
                    }
                    $persons[] = $fp;
                    usort(
                        $persons,
                        static fn (Person $a, Person $b): int => ((int) $a->getId()) <=> ((int) $b->getId()),
                    );
                }
            }
        }
        $ids = [];
        foreach ($persons as $p) {
            if (null !== $p->getId()) {
                $ids[(int) $p->getId()] = true;
            }
        }
        $idList = array_keys($ids);
        sort($idList);

        $focusSlugTrim = \is_string($params->focusPersonSlug) ? trim($params->focusPersonSlug) : '';

        $organizationEntity = null;
        if (\is_string($params->organizationSlug) && '' !== trim($params->organizationSlug)) {
            $candidate = $this->entityManager->getRepository(Organization::class)->findOneBy(['slug' => trim($params->organizationSlug)]);
            if ($candidate instanceof Organization && Organization::STATUS_APPROVED === $candidate->getStatus()) {
                $organizationEntity = $candidate;
            }
        }

        $nodes = [];
        foreach ($persons as $p) {
            $nodes[] = $this->buildPersonGraphNode($p, $focusSlugTrim);
        }

        /** @var list<array<string, mixed>> $edges */
        $edges = [];
        if ([] !== $idList) {
            $conn = $this->entityManager->getConnection();
            $placeholders = implode(',', array_fill(0, \count($idList), '?'));
            $sql = 'SELECT person_a_id, person_b_id, score FROM person_similarities WHERE person_a_id < person_b_id AND person_a_id IN ('.$placeholders.') AND person_b_id IN ('.$placeholders.')';
            $paramsDb = array_merge($idList, $idList);
            $rows = $conn->fetchAllAssociative($sql, $paramsDb);
            foreach ($rows as $row) {
                $a = (int) $row['person_a_id'];
                $b = (int) $row['person_b_id'];
                $edges[] = [
                    'data' => [
                        'id' => 'e-'.$a.'-'.$b,
                        'source' => 'person-'.$a,
                        'target' => 'person-'.$b,
                        'weight' => (float) $row['score'],
                    ],
                ];
            }
        }

        if ([] !== $idList && !$organizationEntity instanceof Organization) {
            $this->appendPersonOrganizationAffiliations(
                $nodes,
                $edges,
                $idList,
                $params->locale,
                null,
                self::MAX_GLOBAL_GRAPH_ORGANIZATIONS,
            );
        }

        if ($organizationEntity instanceof Organization) {
            $oid = (int) $organizationEntity->getId();
            $orgNid = 'org-'.$oid;
            $orgType = $organizationEntity->getType();
            $nodes[] = [
                'data' => [
                    'id' => $orgNid,
                    'label' => $this->localizedContentResolver->resolveOrganizationDisplayName($organizationEntity, $params->locale),
                    'type' => 'organization',
                    'slug' => $organizationEntity->getSlug(),
                    'orgType' => $orgType,
                    'bgColor' => $this->organizationBgColor($orgType),
                ],
                'classes' => 'central',
            ];
            foreach ($persons as $p) {
                $pid = (int) $p->getId();
                $edges[] = [
                    'data' => [
                        'id' => 'e-o-'.$oid.'-p-'.$pid,
                        'source' => $orgNid,
                        'target' => 'person-'.$pid,
                    ],
                ];
            }

            if ([] !== $idList) {
                $this->appendPersonOrganizationAffiliations(
                    $nodes,
                    $edges,
                    $idList,
                    $params->locale,
                    (int) $organizationEntity->getId(),
                );
            }
        }

        $this->appendCoMembersForDisplayedOrganizations(
            $nodes,
            $edges,
            $focusSlugTrim,
        );

        return [
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function buildPersonGraphNode(Person $p, string $focusSlugTrim): array
    {
        $pid = (int) $p->getId();
        $codes = [];
        foreach ($p->getNationalities() as $c) {
            $codes[] = $c->getIsoCode();
        }
        $cat = $p->getRoleCategories()[0] ?? 'other_influencer';
        $node = [
            'data' => [
                'id' => 'person-'.$pid,
                'label' => trim($p->getGivenName().' '.$p->getFamilyName()),
                'type' => 'person',
                'slug' => $p->getSlug(),
                'category' => $cat,
                'countryCodes' => $codes,
                'nodeColor' => self::PERSON_NODE_FILL,
                'bgColor' => self::PERSON_NODE_FILL,
            ],
        ];
        if ('' !== $focusSlugTrim && $p->getSlug() === $focusSlugTrim) {
            $node['classes'] = 'central';
        }

        return $node;
    }

    /**
     * @param list<array<string, mixed>> $nodes
     * @param list<array<string, mixed>> $edges
     *
     * @param-out list<array<string, mixed>> $edges
     */
    private function appendCoMembersForDisplayedOrganizations(
        array &$nodes,
        array &$edges,
        string $focusSlugTrim,
    ): void {
        $orgIds = $this->organizationIdsPresentOnGraph($nodes);
        if ([] === $orgIds) {
            return;
        }

        $existingPersonIds = $this->personIdsPresentOnGraph($nodes);
        if ([] === $existingPersonIds) {
            return;
        }

        $conn = $this->entityManager->getConnection();
        $approved = Organization::STATUS_APPROVED;
        $personApproved = Person::STATUS_APPROVED;

        $orgPh = implode(',', array_fill(0, \count($orgIds), '?'));
        $pPh = implode(',', array_fill(0, \count($existingPersonIds), '?'));
        $notInPerson = ' AND m.person_id NOT IN ('.$pPh.') ';
        $paramsUnion = array_merge($orgIds, [$approved, $personApproved], $existingPersonIds);

        $sql = 'SELECT t.person_id, COUNT(DISTINCT t.organization_id) AS org_links FROM ('
            .'SELECT m.person_id, m.organization_id FROM memberships m '
            .'INNER JOIN persons p ON p.id = m.person_id '
            .'WHERE m.organization_id IN ('.$orgPh.') AND m.status = ? '
            .'AND p.status = ? AND p.deleted_at IS NULL'.$notInPerson
            .' UNION '
            .'SELECT pos.person_id, pos.organization_id FROM positions pos '
            .'INNER JOIN persons p2 ON p2.id = pos.person_id '
            .'WHERE pos.organization_id IN ('.$orgPh.') AND pos.status = ? '
            .'AND p2.status = ? AND p2.deleted_at IS NULL'
            .' AND pos.person_id NOT IN ('.$pPh.')'
            .') t GROUP BY t.person_id ORDER BY org_links DESC, t.person_id ASC LIMIT '.self::MAX_CO_MEMBERS_THROUGH_DISPLAYED_ORGS;

        $paramsUnion = array_merge($paramsUnion, $orgIds, [$approved, $personApproved], $existingPersonIds);

        /** @var list<array{person_id: string|int, org_links: string|int}> $ranked */
        $ranked = $conn->fetchAllAssociative($sql, $paramsUnion);
        if ([] === $ranked) {
            return;
        }

        $newPersonIds = [];
        foreach ($ranked as $row) {
            $newPersonIds[] = (int) $row['person_id'];
        }

        /** @var list<Person> $newPersons */
        $newPersons = $this->personRepository->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->andWhere('p.status = :st')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('ids', $newPersonIds)
            ->setParameter('st', Person::STATUS_APPROVED)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($newPersons as $np) {
            if (null !== $np->getId()) {
                $byId[(int) $np->getId()] = $np;
            }
        }

        $existingNodeIds = [];
        foreach ($nodes as $n) {
            $existingNodeIds[$n['data']['id']] = true;
        }

        foreach ($newPersonIds as $nid) {
            $p = $byId[$nid] ?? null;
            if (!$p instanceof Person) {
                continue;
            }
            $entry = $this->buildPersonGraphNode($p, $focusSlugTrim);
            $pidStr = $entry['data']['id'];
            if (isset($existingNodeIds[$pidStr])) {
                continue;
            }
            $nodes[] = $entry;
            $existingNodeIds[$pidStr] = true;
        }

        $orgIdSet = array_fill_keys($orgIds, true);
        $this->appendPersonOrgEdgesForPersonsAndOrgs($conn, $edges, $newPersonIds, $orgIds, $approved, $orgIdSet);

        $allPersonIds = array_values(array_unique(array_merge($existingPersonIds, $newPersonIds)));
        sort($allPersonIds);
        $this->appendSimilarityEdgesInvolvingNewPersons($conn, $edges, $allPersonIds, $newPersonIds);
    }

    /**
     * @param list<array<string, mixed>> $edges
     * @param list<int>                  $personIds
     * @param list<int>                  $orgIds
     * @param array<int, true>           $orgIdSet
     *
     * @param-out list<array<string, mixed>> $edges
     */
    private function appendPersonOrgEdgesForPersonsAndOrgs(
        Connection $conn,
        array &$edges,
        array $personIds,
        array $orgIds,
        string $orgStatusApproved,
        array $orgIdSet,
    ): void {
        if ([] === $personIds || [] === $orgIds) {
            return;
        }

        $edgeIds = [];
        foreach ($edges as $e) {
            $edgeIds[$e['data']['id']] = true;
        }

        $pPh = implode(',', array_fill(0, \count($personIds), '?'));
        $oPh = implode(',', array_fill(0, \count($orgIds), '?'));
        $baseParams = array_merge($personIds, $orgIds, [$orgStatusApproved, $orgStatusApproved]);

        $sqlM = 'SELECT m.person_id, m.organization_id FROM memberships m '
            .'INNER JOIN organizations o ON o.id = m.organization_id '
            .'WHERE m.person_id IN ('.$pPh.') AND m.organization_id IN ('.$oPh.') '
            .'AND m.status = ? AND o.status = ?';
        $sqlP = 'SELECT pos.person_id, pos.organization_id FROM positions pos '
            .'INNER JOIN organizations o ON o.id = pos.organization_id '
            .'WHERE pos.person_id IN ('.$pPh.') AND pos.organization_id IN ('.$oPh.') '
            .'AND pos.status = ? AND o.status = ?';

        foreach ([$sqlM, $sqlP] as $sql) {
            $rows = $conn->fetchAllAssociative($sql, $baseParams);
            foreach ($rows as $row) {
                $pid = (int) $row['person_id'];
                $oid = (int) $row['organization_id'];
                if (!isset($orgIdSet[$oid])) {
                    continue;
                }
                $eid = 'e-p-'.$pid.'-o-'.$oid;
                if (isset($edgeIds[$eid])) {
                    continue;
                }
                $edgeIds[$eid] = true;
                $edges[] = [
                    'data' => [
                        'id' => $eid,
                        'source' => 'person-'.$pid,
                        'target' => 'org-'.$oid,
                    ],
                ];
            }
        }
    }

    /**
     * @param list<int>                  $allPersonIds
     * @param list<int>                  $newPersonIds
     * @param list<array<string, mixed>> $edges
     *
     * @param-out list<array<string, mixed>> $edges
     */
    private function appendSimilarityEdgesInvolvingNewPersons(
        Connection $conn,
        array &$edges,
        array $allPersonIds,
        array $newPersonIds,
    ): void {
        if ([] === $newPersonIds || [] === $allPersonIds) {
            return;
        }

        $edgeIds = [];
        foreach ($edges as $e) {
            $edgeIds[$e['data']['id']] = true;
        }

        $phAll = implode(',', array_fill(0, \count($allPersonIds), '?'));
        $phNew = implode(',', array_fill(0, \count($newPersonIds), '?'));
        $sql = 'SELECT person_a_id, person_b_id, score FROM person_similarities '
            .'WHERE person_a_id < person_b_id '
            .'AND person_a_id IN ('.$phAll.') AND person_b_id IN ('.$phAll.') '
            .'AND (person_a_id IN ('.$phNew.') OR person_b_id IN ('.$phNew.'))';
        $paramsDb = array_merge($allPersonIds, $allPersonIds, $newPersonIds, $newPersonIds);
        $rows = $conn->fetchAllAssociative($sql, $paramsDb);
        foreach ($rows as $row) {
            $a = (int) $row['person_a_id'];
            $b = (int) $row['person_b_id'];
            $eid = 'e-'.$a.'-'.$b;
            if (isset($edgeIds[$eid])) {
                continue;
            }
            $edgeIds[$eid] = true;
            $edges[] = [
                'data' => [
                    'id' => $eid,
                    'source' => 'person-'.$a,
                    'target' => 'person-'.$b,
                    'weight' => (float) $row['score'],
                ],
            ];
        }
    }

    /**
     * @param list<array<string, mixed>> $nodes
     *
     * @return list<int>
     */
    private function organizationIdsPresentOnGraph(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $n) {
            $type = $n['data']['type'] ?? null;
            if ('organization' !== $type) {
                continue;
            }
            $id = $n['data']['id'] ?? '';
            if (!\is_string($id) || !str_starts_with($id, 'org-')) {
                continue;
            }
            $num = (int) substr($id, 4);
            if ($num > 0) {
                $out[$num] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * @param list<array<string, mixed>> $nodes
     *
     * @return list<int>
     */
    private function personIdsPresentOnGraph(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $n) {
            $type = $n['data']['type'] ?? null;
            if ('person' !== $type) {
                continue;
            }
            $id = $n['data']['id'] ?? '';
            if (!\is_string($id) || !str_starts_with($id, 'person-')) {
                continue;
            }
            $num = (int) substr($id, 7);
            if ($num > 0) {
                $out[$num] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * Ajoute les organisations (adhésions / mandats approuvés) liées aux personnes du sous-graphe.
     *
     * @param list<array<string, mixed>> $nodes
     * @param list<array<string, mixed>> $edges
     * @param list<int>                  $subgraphPersonIds
     * @param int|null                   $excludeOrganizationId exclue du graphe (ex. org. déjà centrale sur la fiche organisation)
     * @param positive-int               $maxOrganizations nombre max d'organisations distinctes (tri par nombre de liens vers le sous-graphe)
     */
    private function appendPersonOrganizationAffiliations(
        array &$nodes,
        array &$edges,
        array $subgraphPersonIds,
        string $locale,
        ?int $excludeOrganizationId,
        int $maxOrganizations = self::MAX_AFFILIATED_ORGANIZATIONS,
    ): void {
        $conn = $this->entityManager->getConnection();
        $placeholders = implode(',', array_fill(0, \count($subgraphPersonIds), '?'));
        $approved = Organization::STATUS_APPROVED;
        $excludeOrgClauseM = null !== $excludeOrganizationId ? ' AND m.organization_id != ?' : '';
        $excludeOrgClauseP = null !== $excludeOrganizationId ? ' AND pos.organization_id != ?' : '';
        $paramsBase = null !== $excludeOrganizationId
            ? array_merge($subgraphPersonIds, [$excludeOrganizationId])
            : $subgraphPersonIds;

        $sqlMemberships = 'SELECT m.person_id, m.organization_id FROM memberships m '
            .'INNER JOIN organizations o ON o.id = m.organization_id '
            .'WHERE m.person_id IN ('.$placeholders.')'.$excludeOrgClauseM.' '
            .'AND m.status = ? AND o.status = ?';

        $sqlPositions = 'SELECT pos.person_id, pos.organization_id FROM positions pos '
            .'INNER JOIN organizations o ON o.id = pos.organization_id '
            .'WHERE pos.person_id IN ('.$placeholders.')'.$excludeOrgClauseP.' '
            .'AND pos.status = ? AND o.status = ?';

        /** @var list<array{person_id: string|int, organization_id: string|int}> $rowsM */
        $rowsM = $conn->fetchAllAssociative($sqlMemberships, array_merge($paramsBase, [$approved, $approved]));
        /** @var list<array{person_id: string|int, organization_id: string|int}> $rowsP */
        $rowsP = $conn->fetchAllAssociative($sqlPositions, array_merge($paramsBase, [$approved, $approved]));

        /** @var array<int, array<int, true>> $orgToPersonKeys */
        $orgToPersonKeys = [];
        /** @var array<string, array{person_id: int, organization_id: int}> $pairByKey */
        $pairByKey = [];
        foreach (array_merge($rowsM, $rowsP) as $row) {
            $pid = (int) $row['person_id'];
            $orgId = (int) $row['organization_id'];
            $pkey = $pid.'-'.$orgId;
            if (isset($pairByKey[$pkey])) {
                continue;
            }
            $pairByKey[$pkey] = ['person_id' => $pid, 'organization_id' => $orgId];
            if (!isset($orgToPersonKeys[$orgId])) {
                $orgToPersonKeys[$orgId] = [];
            }
            $orgToPersonKeys[$orgId][$pid] = true;
        }

        if ([] === $orgToPersonKeys) {
            return;
        }

        $scores = [];
        foreach ($orgToPersonKeys as $orgId => $personKeys) {
            $scores[$orgId] = \count($personKeys);
        }
        arsort($scores, \SORT_NUMERIC);
        $cap = max(1, $maxOrganizations);
        $allowedOrgIds = array_slice(array_keys($scores), 0, $cap);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
            ->from(Organization::class, 'o')
            ->andWhere('o.id IN (:ids)')
            ->andWhere('o.status = :ost')
            ->setParameter('ids', $allowedOrgIds)
            ->setParameter('ost', Organization::STATUS_APPROVED);
        /** @var list<Organization> $orgEntities */
        $orgEntities = $qb->getQuery()->getResult();

        $existingNodeIds = [];
        foreach ($nodes as $n) {
            $existingNodeIds[$n['data']['id']] = true;
        }

        foreach ($orgEntities as $org) {
            $nid = 'org-'.(int) $org->getId();
            if (isset($existingNodeIds[$nid])) {
                continue;
            }
            $orgType = $org->getType();
            $nodes[] = [
                'data' => [
                    'id' => $nid,
                    'label' => $this->localizedContentResolver->resolveOrganizationDisplayName($org, $locale),
                    'type' => 'organization',
                    'slug' => $org->getSlug(),
                    'orgType' => $orgType,
                    'bgColor' => $this->organizationBgColor($orgType),
                ],
            ];
            $existingNodeIds[$nid] = true;
        }

        $allowedSet = array_fill_keys($allowedOrgIds, true);
        foreach ($pairByKey as $pair) {
            $pid = $pair['person_id'];
            $orgId = $pair['organization_id'];
            if (!isset($allowedSet[$orgId])) {
                continue;
            }
            $edges[] = [
                'data' => [
                    'id' => 'e-p-'.$pid.'-o-'.$orgId,
                    'source' => 'person-'.$pid,
                    'target' => 'org-'.$orgId,
                ],
            ];
        }
    }

    private function organizationBgColor(string $orgType): string
    {
        return match ($orgType) {
            Organization::TYPE_INFLUENCE_NETWORK => '#7B1A1A',
            Organization::TYPE_POLITICAL_PARTY => '#1A2C5B',
            Organization::TYPE_CORPORATION => '#A84B27',
            Organization::TYPE_MEDIA_GROUP => '#5C1A6B',
            Organization::TYPE_GOVERNMENT_BODY => '#1F4D3F',
            Organization::TYPE_INTERNATIONAL_BODY => '#0F4D5B',
            Organization::TYPE_THINK_TANK => '#854F0B',
            Organization::TYPE_LOBBY_GROUP => '#5C1313',
            default => '#5A5650',
        };
    }
}
