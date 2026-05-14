<?php

declare(strict_types=1);

namespace App\Module\Graph\Service;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Shared\I18n\LocalizedContentResolver;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Données JSON Cytoscape pour le mini-graphe centré organisation + membres approuvés.
 */
final class OrganizationMiniGraphBuilder
{
    private const int MAX_MEMBERS = 80;

    public function __construct(
        private readonly PersonRepository $personRepository,
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
    public function build(Organization $organization): array
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';
        $orgId = (int) $organization->getId();
        $orgNodeId = 'org-'.$orgId;

        $nodes = [
            $this->organizationNode($organization, $locale, true),
        ];
        $nodeIds = [$orgNodeId => true];
        $edges = [];

        $members = $this->personRepository->findApprovedMembersForOrganization(
            $organization,
            null,
            self::MAX_MEMBERS,
            0,
        );

        foreach ($members as $person) {
            if (Person::STATUS_APPROVED !== $person->getStatus()) {
                continue;
            }
            $pid = (int) $person->getId();
            $pNodeId = 'person-'.$pid;
            if (!isset($nodeIds[$pNodeId])) {
                $nodes[] = $this->personNode($person);
                $nodeIds[$pNodeId] = true;
            }
            $edges[] = [
                'data' => [
                    'id' => 'e-o-'.$orgId.'-p-'.$pid,
                    'source' => $orgNodeId,
                    'target' => $pNodeId,
                ],
            ];
        }

        return [
            'analyzing' => false,
            'connectionCount' => \count($edges),
            'elements' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function organizationNode(Organization $organization, string $locale, bool $central): array
    {
        $id = (int) $organization->getId();
        $row = [
            'data' => [
                'id' => 'org-'.$id,
                'label' => $this->localizedContentResolver->resolveOrganizationDisplayName($organization, $locale),
                'type' => 'organization',
                'orgType' => $organization->getType(),
                'slug' => $organization->getSlug(),
            ],
        ];
        if ($central) {
            $row['classes'] = 'central';
        }

        return $row;
    }

    /** @return array<string, mixed> */
    private function personNode(Person $person): array
    {
        $id = (int) $person->getId();

        return [
            'data' => [
                'id' => 'person-'.$id,
                'label' => trim($person->getGivenName().' '.$person->getFamilyName()),
                'type' => 'person',
                'category' => $this->primaryCategory($person),
                'slug' => $person->getSlug(),
            ],
        ];
    }

    private function primaryCategory(Person $person): string
    {
        $cats = $person->getRoleCategories();
        if ([] === $cats) {
            return 'other_influencer';
        }

        return $cats[0];
    }
}
