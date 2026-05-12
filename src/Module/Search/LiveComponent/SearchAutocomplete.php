<?php

declare(strict_types=1);

namespace App\Module\Search\LiveComponent;

use App\Module\Search\Service\SearchQueryService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(name: 'SearchAutocomplete', template: 'search/live_component/search_autocomplete.html.twig', method: 'get')]
final class SearchAutocomplete
{
    use DefaultActionTrait;

    public function __construct(
        private readonly SearchQueryService $searchQueryService,
    ) {
    }

    #[LiveProp(writable: true)]
    public string $q = '';

    /**
     * @return array{persons: list<array<string, mixed>>, organizations: list<array<string, mixed>>}
     */
    #[ExposeInTemplate('autocomplete')]
    public function getAutocomplete(): array
    {
        return $this->searchQueryService->autocomplete($this->q);
    }
}
