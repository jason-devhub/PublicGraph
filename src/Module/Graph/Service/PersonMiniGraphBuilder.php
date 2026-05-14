<?php

declare(strict_types=1);

namespace App\Module\Graph\Service;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use App\Shared\I18n\LocalizedContentResolver;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Données JSON Cytoscape pour le mini-graphe fiche Person (T3.7).
 */
final class PersonMiniGraphBuilder
{
    public function __construct(
        private readonly PersonSimilarityRepository $personSimilarityRepository,
        private readonly LocalizedContentResolver $localizedContentResolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array{
     *     analyzing: bool,
     *     connectionCount: int,
     *     elements: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     * }
     */
    public function build(Person $person): array
    {
        $centralId = (int) $person->getId();
        $similarities = $this->personSimilarityRepository->findTopForPerson($person, 20);

        $nodes = [];
        $edges = [];
        $nodeIds = [];

        $nodes[] = $this->personNode($person, true);
        $nodeIds['person-'.$centralId] = true;

        foreach ($similarities as $similarity) {
            $other = $similarity->getPersonB();
            if (!$other instanceof Person || Person::STATUS_APPROVED !== $other->getStatus()) {
                continue;
            }
            $oid = (int) $other->getId();
            $pid = 'person-'.$oid;
            if (!isset($nodeIds[$pid])) {
                $nodes[] = $this->personNode($other, false);
                $nodeIds[$pid] = true;
            }
            $edges[] = [
                'data' => [
                    'id' => 'e-p-'.$centralId.'-p-'.$oid,
                    'source' => 'person-'.$centralId,
                    'target' => $pid,
                    'weight' => (float) $similarity->getScore(),
                ],
            ];
        }

        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        foreach ($this->topOrganizationsForPerson($person) as $org) {
            $orgId = (int) $org->getId();
            $nid = 'org-'.$orgId;
            if (!isset($nodeIds[$nid])) {
                $nodes[] = [
                    'data' => [
                        'id' => $nid,
                        'label' => $this->localizedContentResolver->resolveOrganizationDisplayName($org, $locale),
                        'type' => 'organization',
                        'orgType' => $org->getType(),
                        'slug' => $org->getSlug(),
                    ],
                ];
                $nodeIds[$nid] = true;
            }
            $edges[] = [
                'data' => [
                    'id' => 'e-p-'.$centralId.'-o-'.$orgId,
                    'source' => 'person-'.$centralId,
                    'target' => $nid,
                ],
            ];
        }

        $hasConnections = [] !== $edges;

        return [
            'analyzing' => !$hasConnections,
            'connectionCount' => \count($edges),
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function personNode(Person $person, bool $central): array
    {
        $id = (int) $person->getId();

        $row = [
            'data' => [
                'id' => 'person-'.$id,
                'label' => trim($person->getGivenName().' '.$person->getFamilyName()),
                'type' => 'person',
                'category' => $this->primaryCategory($person),
                'slug' => $person->getSlug(),
            ],
        ];
        if ($central) {
            $row['classes'] = 'central';
        }

        return $row;
    }

    private function primaryCategory(Person $person): string
    {
        $cats = $person->getRoleCategories();
        if ([] === $cats) {
            return 'other_influencer';
        }

        return $cats[0];
    }

    /** @return list<Organization> */
    private function topOrganizationsForPerson(Person $person): array
    {
        /** @var list<Membership> $approved */
        $approved = $person->getMemberships()->filter(
            static fn (Membership $m): bool => 'approved' === $m->getStatus(),
        )->getValues();

        /** @var array<int, array{org: Organization, maxYear: int, count: int}> $stats */
        $stats = [];
        foreach ($approved as $m) {
            $org = $m->getOrganization();
            if (!$org instanceof Organization || Organization::STATUS_APPROVED !== $org->getStatus()) {
                continue;
            }
            $oid = (int) $org->getId();
            $year = $m->getYear() ?? 0;
            if (!isset($stats[$oid])) {
                $stats[$oid] = ['org' => $org, 'maxYear' => $year, 'count' => 1];
            } else {
                $stats[$oid]['maxYear'] = max($stats[$oid]['maxYear'], $year);
                ++$stats[$oid]['count'];
            }
        }

        $list = array_values($stats);
        usort(
            $list,
            static function (array $a, array $b): int {
                if ($a['maxYear'] !== $b['maxYear']) {
                    return $b['maxYear'] <=> $a['maxYear'];
                }
                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count'];
                }

                return $a['org']->getOfficialName() <=> $b['org']->getOfficialName();
            },
        );

        $out = [];
        foreach (\array_slice($list, 0, 10) as $row) {
            $out[] = $row['org'];
        }

        return $out;
    }
}
