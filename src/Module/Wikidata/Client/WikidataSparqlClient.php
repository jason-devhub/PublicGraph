<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class WikidataSparqlClient
{
    private const ENDPOINT = 'https://query.wikidata.org/sparql';

    private const USER_AGENT = 'PublicGraph/1.0 (https://publicgraph.org; contact@publicgraph.org)';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array<string, mixed> Décodage JSON results.bindings structure WDQS
     */
    public function query(string $sparql): array
    {
        $response = $this->requestWithRetry($sparql);

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!\is_array($data)) {
            throw new \RuntimeException('Réponse SPARQL JSON invalide.');
        }

        return $data;
    }

    /**
     * @return list<array<string, array{type: string, value: string}>>
     */
    public function queryBindings(string $sparql): array
    {
        $data = $this->query($sparql);
        $bindings = $data['results']['bindings'] ?? null;
        if (!\is_array($bindings)) {
            return [];
        }

        $list = array_values($bindings);

        /* @var list<array<string, array{type: string, value: string}>> $list */
        return $list;
    }

    /** @return ?array<string, array{type: string, value: string}> */
    public function findPersonByQid(string $qid): ?array
    {
        $qid = $this->normalizeQid($qid);
        $sparql = str_replace('{{QID}}', $qid, $this->loadTemplate('query_person_by_qid.sparql'));
        $rows = $this->queryBindings($sparql);

        return $rows[0] ?? null;
    }

    /**
     * @return list<array<string, array{type: string, value: string}>>
     */
    public function searchPersonByName(string $name, ?string $countryIso = null): array
    {
        $name = trim($name);
        if ('' === $name) {
            return [];
        }
        $countryFilter = '';
        if (null !== $countryIso && '' !== $countryIso) {
            $qids = WikidataCountryQids::nationalityQidsForIso(strtoupper($countryIso));
            if ([] !== $qids) {
                $list = implode(' ', array_map(static fn (string $q): string => 'wd:'.$q, $qids));
                $countryFilter = 'VALUES ?country { '.$list.' } ?person wdt:P27 ?country .';
            }
        }
        $escaped = str_replace(['\\', '"'], ['\\\\', '\"'], $name);
        $sparql = <<<SPARQL
SELECT ?person ?personLabel (STRAFTER(STR(?person), "http://www.wikidata.org/entity/") AS ?wikidataId)
WHERE {
  ?person wdt:P31 wd:Q5 ;
          rdfs:label ?personLabel .
  FILTER(LANG(?personLabel) IN ("fr", "en"))
  FILTER(CONTAINS(LCASE(?personLabel), LCASE("{$escaped}")))
  {$countryFilter}
  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
}
LIMIT 50
SPARQL;

        return $this->queryBindings($sparql);
    }

    /**
     * @return list<array<string, array{type: string, value: string}>>
     */
    public function searchOrganizationByName(string $name): array
    {
        $name = trim($name);
        if ('' === $name) {
            return [];
        }
        $escaped = str_replace(['\\', '"'], ['\\\\', '\"'], $name);
        $sparql = <<<SPARQL
SELECT ?org ?orgLabel (STRAFTER(STR(?org), "http://www.wikidata.org/entity/") AS ?wikidataId)
       (SAMPLE(?instance) AS ?instanceOf)
WHERE {
  ?org wdt:P31 ?instance .
  ?org rdfs:label ?orgLabel .
  FILTER(LANG(?orgLabel) IN ("fr", "en"))
  FILTER(CONTAINS(LCASE(?orgLabel), LCASE("{$escaped}")))
  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
}
GROUP BY ?org ?orgLabel
LIMIT 50
SPARQL;

        return $this->queryBindings($sparql);
    }

    /**
     * @param list<string> $countryQids    ex. ['Q142']
     * @param list<string> $occupationQids ex. ['wd:Q82955', …] ou ['Q82955']
     */
    public function buildPersonsByCountryQuery(array $countryQids, array $occupationQids, int $limit): string
    {
        $countryList = implode(' ', array_map(static fn (string $q): string => str_starts_with($q, 'wd:') ? $q : 'wd:'.$q, $countryQids));
        $occList = implode(' ', array_map(static fn (string $q): string => str_starts_with($q, 'wd:') ? $q : 'wd:'.$q, $occupationQids));

        return str_replace(
            ['{{COUNTRY_QIDS}}', '{{OCCUPATION_QIDS}}', '{{LIMIT}}'],
            [$countryList, $occList, (string) max(1, min($limit, 5000))],
            $this->loadTemplate('query_persons_by_country.sparql'),
        );
    }

    private function loadTemplate(string $filename): string
    {
        $path = dirname(__DIR__).'/Resources/sparql/'.$filename;
        if (!is_file($path)) {
            throw new \InvalidArgumentException('Fichier SPARQL introuvable : '.$filename);
        }

        return (string) file_get_contents($path);
    }

    private function requestWithRetry(string $sparql): ResponseInterface
    {
        $maxAttempts = 3;
        $delayMs = 500;
        $lastException = null;
        for ($i = 0; $i < $maxAttempts; ++$i) {
            try {
                $response = $this->httpClient->request('POST', self::ENDPOINT, [
                    'headers' => [
                        'User-Agent' => self::USER_AGENT,
                        'Accept' => 'application/sparql-results+json',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => http_build_query(['query' => $sparql], '', '&', PHP_QUERY_RFC3986),
                    'timeout' => 30,
                ]);
                $status = $response->getStatusCode();
                if (429 === $status) {
                    usleep($delayMs * 1000);
                    $delayMs *= 2;

                    continue;
                }
                $response->getHeaders();
                if ($status >= 400) {
                    throw new \RuntimeException('SPARQL HTTP '.$status.' : '.$response->getContent(false));
                }

                return $response;
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($i < $maxAttempts - 1) {
                    usleep($delayMs * 1000);
                    $delayMs *= 2;
                }
            }
        }
        if ($lastException instanceof \Throwable) {
            throw new \RuntimeException('Échec requête Wikidata après retries.', 0, $lastException);
        }

        throw new \RuntimeException('Échec requête Wikidata.');
    }

    private function normalizeQid(string $qid): string
    {
        $qid = trim($qid);
        if (str_starts_with(strtolower($qid), 'wd:')) {
            return substr($qid, 3);
        }

        return $qid;
    }
}
