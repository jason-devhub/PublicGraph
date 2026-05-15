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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GraphDataBuilder
{
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
     * @return array{elements: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}}
     */
    private function buildUncached(GraphQueryParams $params): array
    {
        /** @var array<int, Person> $forcedFocusPersons */
        $forcedFocusPersons = [];
        if (\is_string($params->focusPersonSlug) && '' !== trim($params->focusPersonSlug)) {
            $fp = $this->personRepository->findBySlug(trim($params->focusPersonSlug));
            if ($fp instanceof Person && Person::STATUS_APPROVED === $fp->getStatus() && null === $fp->getDeletedAt()) {
                $forcedFocusPersons[(int) $fp->getId()] = $fp;
            }
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

        if ([] !== $forcedFocusPersons) {
            $qb->andWhere('p.id NOT IN (:_pgFocusExcl)')
                ->setParameter('_pgFocusExcl', array_keys($forcedFocusPersons));
        }

        $remainingSlots = max(0, $params->maxNodes - \count($forcedFocusPersons));
        if ($remainingSlots > 0) {
            $qb->setMaxResults($remainingSlots);
            /** @var list<Person> $fetched */
            $fetched = $qb->getQuery()->getResult();
        } else {
            $fetched = [];
        }

        /** @var array<int, Person> $merged */
        $merged = $forcedFocusPersons;
        foreach ($fetched as $p) {
            $pid = (int) $p->getId();
            if (!isset($merged[$pid])) {
                $merged[$pid] = $p;
            }
        }
        /** @var list<Person> $persons */
        $persons = array_values($merged);
        usort(
            $persons,
            static fn (Person $a, Person $b): int => ((int) $a->getId()) <=> ((int) $b->getId()),
        );
        $ids = [];
        foreach ($persons as $p) {
            if (null !== $p->getId()) {
                $ids[(int) $p->getId()] = true;
            }
        }
        $idList = array_keys($ids);
        sort($idList);

        $focusSlugTrim = \is_string($params->focusPersonSlug) ? trim($params->focusPersonSlug) : '';

        $nodes = [];
        foreach ($persons as $p) {
            $pid = (int) $p->getId();
            $codes = [];
            foreach ($p->getNationalities() as $c) {
                $codes[] = $c->getIsoCode();
            }
            $cat = $p->getRoleCategories()[0] ?? 'other_influencer';
            $nodeColor = $this->nodeColor($cat, $codes, $params->colorMode);
            $node = [
                'data' => [
                    'id' => 'person-'.$pid,
                    'label' => trim($p->getGivenName().' '.$p->getFamilyName()),
                    'type' => 'person',
                    'slug' => $p->getSlug(),
                    'category' => $cat,
                    'countryCodes' => $codes,
                    'nodeColor' => $nodeColor,
                    'bgColor' => $nodeColor,
                ],
            ];
            if ('' !== $focusSlugTrim && $p->getSlug() === $focusSlugTrim) {
                $node['classes'] = 'central';
            }
            $nodes[] = $node;
        }

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

        $organizationEntity = null;
        if (\is_string($params->organizationSlug) && '' !== trim($params->organizationSlug)) {
            $candidate = $this->entityManager->getRepository(Organization::class)->findOneBy(['slug' => trim($params->organizationSlug)]);
            if ($candidate instanceof Organization && Organization::STATUS_APPROVED === $candidate->getStatus()) {
                $organizationEntity = $candidate;
            }
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
        }

        return [
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
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

    /** @param list<string> $countryCodes */
    private function nodeColor(string $category, array $countryCodes, string $colorMode): string
    {
        if ('country' === $colorMode) {
            $c = $countryCodes[0] ?? 'ZZ';
            $hue = (ord($c[0]) * 37 + (isset($c[1]) ? ord($c[1]) : 0) * 17) % 360;

            return sprintf('hsl(%d, 45%%, 42%%)', $hue);
        }

        return match ($category) {
            'politician' => '#1A2C5B',
            'civil_servant' => '#5A5650',
            'business_leader' => '#A84B27',
            'media_owner' => '#5C1A6B',
            'financier' => '#1F4D3F',
            'lobbyist' => '#7B1A1A',
            default => '#8A8680',
        };
    }
}
