<?php

declare(strict_types=1);

namespace App\Module\Graph\Controller;

use App\Module\Graph\Model\GraphQueryParams;
use App\Module\Graph\Service\GraphDataBuilder;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizationGraphDataController extends AbstractController
{
    #[Route('/organizations/{slug}/graph-data', name: 'app_organization_graph_data', methods: ['GET'])]
    public function __invoke(
        Request $request,
        string $slug,
        OrganizationRepository $organizationRepository,
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

        $organization = $organizationRepository->findOneBy(['slug' => $slug]);
        if (!$organization instanceof Organization || Organization::STATUS_APPROVED !== $organization->getStatus()) {
            throw new NotFoundHttpException('Organisation introuvable.');
        }

        $params = new GraphQueryParams(
            organizationSlug: $organization->getSlug(),
            maxNodes: 200,
            locale: $request->getLocale(),
        );
        $built = $graphDataBuilder->build($params);
        $elements = $built['elements'];
        $payload = [
            'analyzing' => false,
            'connectionCount' => \count($elements['edges']),
            'elements' => $elements,
        ];
        $response = new JsonResponse($payload);
        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }
}
