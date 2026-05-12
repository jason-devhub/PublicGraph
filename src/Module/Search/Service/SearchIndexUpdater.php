<?php

declare(strict_types=1);

namespace App\Module\Search\Service;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Search\Client\MeilisearchClient;

final class SearchIndexUpdater
{
    public function __construct(
        private readonly MeilisearchClient $meilisearchClient,
        private readonly SearchDocumentFactory $documentFactory,
    ) {
    }

    public function syncPerson(Person $person): void
    {
        $id = $person->getId();
        if (null === $id) {
            return;
        }

        if (!$this->documentFactory->shouldIndexPerson($person)) {
            $this->meilisearchClient->deletePersonDocument((string) $id);

            return;
        }

        $doc = $this->documentFactory->buildPersonDocument($person);
        $this->meilisearchClient->upsertPersonDocuments([$doc]);
    }

    public function removePerson(int $id): void
    {
        $this->meilisearchClient->deletePersonDocument((string) $id);
    }

    public function syncOrganization(Organization $organization): void
    {
        $id = $organization->getId();
        if (null === $id) {
            return;
        }

        if (!$this->documentFactory->shouldIndexOrganization($organization)) {
            $this->meilisearchClient->deleteOrganizationDocument((string) $id);

            return;
        }

        $doc = $this->documentFactory->buildOrganizationDocument($organization);
        $this->meilisearchClient->upsertOrganizationDocuments([$doc]);
    }

    public function removeOrganization(int $id): void
    {
        $this->meilisearchClient->deleteOrganizationDocument((string) $id);
    }
}
