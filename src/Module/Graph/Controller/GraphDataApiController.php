<?php

declare(strict_types=1);

namespace App\Module\Graph\Controller;

use App\Module\Graph\Model\GraphQueryParams;
use App\Module\Graph\Service\GraphDataBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class GraphDataApiController
{
    #[Route('/api/graph/data', name: 'app_api_graph_data', methods: ['GET'])]
    public function __invoke(
        Request $request,
        GraphDataBuilder $graphDataBuilder,
        #[Autowire(service: 'limiter.graph_api_ip')]
        RateLimiterFactory $graphApiLimiter,
    ): JsonResponse {
        $rateLimit = $graphApiLimiter->create($request->getClientIp() ?? '0.0.0.0')->consume(1);
        if (!$rateLimit->isAccepted()) {
            $retryAfter = max(1, $rateLimit->getRetryAfter()->getTimestamp() - time());
            $response = new JsonResponse(['error' => 'Too many requests'], 429);
            $response->headers->set('Retry-After', (string) $retryAfter);

            return $response;
        }

        $params = GraphQueryParams::fromRequest($request);
        $payload = $graphDataBuilder->build($params);
        $response = new JsonResponse($payload);
        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }
}
