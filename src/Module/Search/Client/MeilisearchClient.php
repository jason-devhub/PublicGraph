<?php

declare(strict_types=1);

namespace App\Module\Search\Client;

use Meilisearch\Client as MeilisearchSdkClient;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;

final class MeilisearchClient
{
    public const INDEX_PERSONS = 'persons';

    public const INDEX_ORGANIZATIONS = 'organizations';

    private MeilisearchSdkClient $sdk;

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        string $meilisearchUrl,
        #[\SensitiveParameter]
        ?string $meilisearchKey,
        private readonly array $enabledLocales,
    ) {
        $this->sdk = new MeilisearchSdkClient($meilisearchUrl, $meilisearchKey ?: null);
    }

    public function getSdk(): MeilisearchSdkClient
    {
        return $this->sdk;
    }

    /**
     * Crée les index s’ils manquent et applique les réglages de recherche.
     */
    public function ensureIndexes(): void
    {
        $personSearchable = [
            'fullName',
            'usageName',
            'slug',
            'role_categories',
            'nationalities',
            'organizations',
        ];
        foreach ($this->enabledLocales as $loc) {
            $personSearchable[] = 'description_'.$loc;
        }

        $this->ensureIndex(self::INDEX_PERSONS, [
            'searchableAttributes' => $personSearchable,
            'displayedAttributes' => ['*'],
        ]);

        $orgSearchable = [
            'officialName',
            'slug',
            'type',
            'countries',
        ];
        foreach ($this->enabledLocales as $loc) {
            $orgSearchable[] = 'translated_name_'.$loc;
            $orgSearchable[] = 'org_description_'.$loc;
        }

        $this->ensureIndex(self::INDEX_ORGANIZATIONS, [
            'searchableAttributes' => $orgSearchable,
            'displayedAttributes' => ['*'],
        ]);
    }

    /**
     * @param list<SearchQuery> $queries
     *
     * @return list<SearchResult>
     */
    public function multiSearch(array $queries): array
    {
        /** @var array{results: array<int, array<string, mixed>>} $raw */
        $raw = $this->sdk->multiSearch($queries);

        $out = [];
        foreach ($raw['results'] as $body) {
            $out[] = new SearchResult($body);
        }

        return $out;
    }

    public function searchPersons(SearchQuery $query): SearchResult
    {
        $arr = $query->toArray();
        $q = (string) ($arr['q'] ?? '');
        unset($arr['q'], $arr['indexUid']);
        $body = $this->sdk->index(self::INDEX_PERSONS)->rawSearch('' !== $q ? $q : null, $arr);

        return new SearchResult($body);
    }

    public function searchOrganizations(SearchQuery $query): SearchResult
    {
        $arr = $query->toArray();
        $q = (string) ($arr['q'] ?? '');
        unset($arr['q'], $arr['indexUid']);
        $body = $this->sdk->index(self::INDEX_ORGANIZATIONS)->rawSearch('' !== $q ? $q : null, $arr);

        return new SearchResult($body);
    }

    /**
     * @param list<array<string, mixed>> $documents
     */
    public function upsertPersonDocuments(array $documents): void
    {
        if ([] === $documents) {
            return;
        }

        $this->sdk->index(self::INDEX_PERSONS)->addDocuments($documents, 'id');
    }

    /**
     * @param list<array<string, mixed>> $documents
     */
    public function upsertOrganizationDocuments(array $documents): void
    {
        if ([] === $documents) {
            return;
        }

        $this->sdk->index(self::INDEX_ORGANIZATIONS)->addDocuments($documents, 'id');
    }

    public function deletePersonDocument(string $id): void
    {
        try {
            $this->sdk->index(self::INDEX_PERSONS)->deleteDocument($id);
        } catch (ApiException $e) {
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }
    }

    public function deleteOrganizationDocument(string $id): void
    {
        try {
            $this->sdk->index(self::INDEX_ORGANIZATIONS)->deleteDocument($id);
        } catch (ApiException $e) {
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }
    }

    public function deleteAllPersonDocuments(): void
    {
        $this->sdk->index(self::INDEX_PERSONS)->deleteAllDocuments();
    }

    public function deleteAllOrganizationDocuments(): void
    {
        $this->sdk->index(self::INDEX_ORGANIZATIONS)->deleteAllDocuments();
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function ensureIndex(string $uid, array $settings): void
    {
        try {
            $this->sdk->createIndex($uid, ['primaryKey' => 'id']);
        } catch (ApiException $e) {
            if (409 !== $e->getCode() && 'index_already_exists' !== ($e->errorCode ?? null)) {
                throw $e;
            }
        }

        $this->sdk->index($uid)->updateSettings($settings);
    }
}
