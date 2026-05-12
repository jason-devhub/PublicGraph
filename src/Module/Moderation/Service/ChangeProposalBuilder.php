<?php

declare(strict_types=1);

namespace App\Module\Moderation\Service;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;

/**
 * Construit une ChangeProposal avec diff calculé (identité Person / Organization MVP).
 */
final class ChangeProposalBuilder
{
    /**
     * @param array<string, mixed> $newValues champs cibles => nouvelle valeur
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function buildPersonIdentityDiff(Person $person, array $newValues): array
    {
        $diff = [];
        foreach ($newValues as $field => $newVal) {
            $old = match ($field) {
                'givenName' => $person->getGivenName(),
                'familyName' => $person->getFamilyName(),
                'usageName' => $person->getUsageName(),
                default => null,
            };
            if ($old !== $newVal) {
                $diff[$field] = ['old' => $old, 'new' => $newVal];
            }
        }

        return $diff;
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}> $diff
     */
    public function createProposal(
        string $entityType,
        int $entityId,
        array $diff,
        string $justification,
        User $submitter,
    ): ChangeProposal {
        $p = new ChangeProposal();
        $p->setEntityType($entityType);
        $p->setEntityId($entityId);
        $p->setDiff($diff);
        $p->setJustification($justification);
        $p->setSubmittedBy($submitter);

        return $p;
    }

    /**
     * @param array<string, mixed> $newValues
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function buildOrganizationIdentityDiff(Organization $organization, array $newValues): array
    {
        $diff = [];
        foreach ($newValues as $field => $newVal) {
            $old = match ($field) {
                'officialName' => $organization->getOfficialName(),
                'type' => $organization->getType(),
                default => null,
            };
            if ($old !== $newVal) {
                $diff[$field] = ['old' => $old, 'new' => $newVal];
            }
        }

        return $diff;
    }
}
