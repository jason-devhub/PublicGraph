<?php

declare(strict_types=1);

namespace App\Module\Search\Service;

use App\Module\Search\Client\MeilisearchClient;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Search\SearchResult;
use Symfony\Component\HttpFoundation\RequestStack;

final class SearchQueryService
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly MeilisearchClient $meilisearchClient,
        private readonly RequestStack $requestStack,
        private readonly array $enabledLocales,
    ) {
    }

    public function searchPersons(string $q, int $page, int $perPage): SearchResult
    {
        $offset = max(0, ($page - 1) * $perPage);
        $locale = $this->currentLocale();
        $descField = 'description_'.$locale;
        $attrs = ['fullName', 'usageName', 'slug', 'role_categories', 'nationalities', 'organizations', $descField];

        $sq = (new SearchQuery())
            ->setQuery($q)
            ->setLimit($perPage)
            ->setOffset($offset)
            ->setAttributesToSearchOn($attrs);

        return $this->meilisearchClient->searchPersons($sq);
    }

    public function searchOrganizations(string $q, int $page, int $perPage): SearchResult
    {
        $offset = max(0, ($page - 1) * $perPage);
        $locale = $this->currentLocale();
        $nameField = 'translated_name_'.$locale;
        $descField = 'org_description_'.$locale;
        $attrs = ['officialName', 'slug', 'type', 'countries', $nameField, $descField];

        $sq = (new SearchQuery())
            ->setQuery($q)
            ->setLimit($perPage)
            ->setOffset($offset)
            ->setAttributesToSearchOn($attrs);

        return $this->meilisearchClient->searchOrganizations($sq);
    }

    /**
     * @return array{persons: list<array<string, mixed>>, organizations: list<array<string, mixed>>}
     */
    public function autocomplete(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return ['persons' => [], 'organizations' => []];
        }

        $locale = $this->currentLocale();
        $personAttrs = ['fullName', 'usageName', 'slug', 'role_categories', 'nationalities', 'organizations', 'description_'.$locale];
        $orgAttrs = ['officialName', 'slug', 'type', 'countries', 'translated_name_'.$locale, 'org_description_'.$locale];

        $queries = [
            (new SearchQuery())
                ->setIndexUid(MeilisearchClient::INDEX_PERSONS)
                ->setQuery($query)
                ->setLimit(5)
                ->setAttributesToRetrieve(['id', 'slug', 'fullName'])
                ->setAttributesToSearchOn($personAttrs),
            (new SearchQuery())
                ->setIndexUid(MeilisearchClient::INDEX_ORGANIZATIONS)
                ->setQuery($query)
                ->setLimit(5)
                ->setAttributesToRetrieve(['id', 'slug', 'officialName'])
                ->setAttributesToSearchOn($orgAttrs),
        ];

        $results = $this->meilisearchClient->multiSearch($queries);

        return [
            'persons' => $results[0]->getHits(),
            'organizations' => $results[1]->getHits(),
        ];
    }

    private function currentLocale(): string
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();
        if (\is_string($locale) && \in_array($locale, $this->enabledLocales, true)) {
            return $locale;
        }

        return $this->enabledLocales[0] ?? 'en';
    }
}
