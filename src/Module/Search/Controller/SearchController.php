<?php

declare(strict_types=1);

namespace App\Module\Search\Controller;

use App\Module\Search\Service\SearchQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchQueryService $searchQueryService,
        #[Autowire(service: 'limiter.search_api_ip')]
        private readonly RateLimiterFactory $searchApiLimiter,
    ) {
    }

    /** Legacy French path → canonical search (même locale). */
    #[Route('/recherche', name: 'app_search_recherche', methods: ['GET'])]
    public function recherche(Request $request): Response
    {
        if ($r = $this->applySearchRateLimit($request)) {
            return $r;
        }

        return $this->redirectToRoute('app_search_index', [
            '_locale' => $request->getLocale(),
            'q' => $request->query->get('q'),
            'type' => $request->query->get('type') ?? 'all',
            'page' => $request->query->get('page') ?? 1,
            'pp' => $request->query->get('pp') ?? 1,
            'op' => $request->query->get('op') ?? 1,
        ], Response::HTTP_FOUND);
    }

    #[Route('/search', name: 'app_search_index', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        if ($r = $this->applySearchRateLimit($request)) {
            return $r;
        }

        $q = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all');
        $page = max(1, (int) $request->query->get('page', 1));
        $pagePersons = max(1, (int) $request->query->get('pp', 1));
        $pageOrgs = max(1, (int) $request->query->get('op', 1));

        if (!\in_array($type, ['all', 'persons', 'organizations'], true)) {
            $type = 'all';
        }

        $perPage = 20;

        $personsResult = null;
        $organizationsResult = null;

        if ('' !== $q) {
            if ('all' === $type || 'persons' === $type) {
                $personsResult = $this->searchQueryService->searchPersons(
                    $q,
                    'all' === $type ? $pagePersons : $page,
                    $perPage,
                );
            }
            if ('all' === $type || 'organizations' === $type) {
                $organizationsResult = $this->searchQueryService->searchOrganizations(
                    $q,
                    'all' === $type ? $pageOrgs : $page,
                    $perPage,
                );
            }
        }

        return $this->render('search/index.html.twig', [
            'q' => $q,
            'type' => $type,
            'personsResult' => $personsResult,
            'organizationsResult' => $organizationsResult,
            'page' => $page,
            'pagePersons' => $pagePersons,
            'pageOrgs' => $pageOrgs,
            'perPage' => $perPage,
        ]);
    }

    private function applySearchRateLimit(Request $request): ?Response
    {
        $rateLimit = $this->searchApiLimiter->create($request->getClientIp() ?? '0.0.0.0')->consume(1);
        if ($rateLimit->isAccepted()) {
            return null;
        }

        $retryAfter = max(1, $rateLimit->getRetryAfter()->getTimestamp() - time());

        return new Response('Too many requests', 429, ['Retry-After' => (string) $retryAfter]);
    }
}
