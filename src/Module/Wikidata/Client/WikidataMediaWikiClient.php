<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Appels légers à l’API Wikidata (wbgetentities) pour résoudre un titre de wiki en QID.
 */
final class WikidataMediaWikiClient
{
    private const USER_AGENT = 'PublicGraph/1.0 (https://publicgraph.org; contact@publicgraph.org)';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Résout un titre de page (ex. « Le_Siècle ») sur un site Wikimedia vers l’ID d’entité Wikidata.
     *
     * @param non-empty-string $site  ex. frwiki
     * @param non-empty-string $title titre exact côté wiki (souvent avec underscores)
     */
    public function qidFromSiteTitle(string $site, string $title): ?string
    {
        $site = trim($site);
        $title = trim($title);
        if ('' === $site || '' === $title) {
            return null;
        }
        $url = 'https://www.wikidata.org/w/api.php?'.http_build_query([
            'action' => 'wbgetentities',
            'sites' => $site,
            'titles' => $title,
            'format' => 'json',
        ], '', '&', PHP_QUERY_RFC3986);
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
            'timeout' => 30,
        ]);
        if (200 !== $response->getStatusCode()) {
            return null;
        }
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!\is_array($data)) {
            return null;
        }
        $entities = $data['entities'] ?? null;
        if (!\is_array($entities) || [] === $entities) {
            return null;
        }
        $first = reset($entities);
        if (!\is_array($first)) {
            return null;
        }
        $id = $first['id'] ?? null;
        if (!\is_string($id) || !preg_match('/^Q\d+$/', $id)) {
            return null;
        }

        return $id;
    }
}
