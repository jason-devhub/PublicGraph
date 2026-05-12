<?php

declare(strict_types=1);

namespace App\Module\Graph\Controller;

use App\Module\Graph\Service\PersonMiniGraphBuilder;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class PersonGraphDataController extends AbstractController
{
    #[Route('/people/{slug}/graph-data', name: 'app_person_graph_data', methods: ['GET'])]
    public function __invoke(
        Request $request,
        string $slug,
        PersonRepository $personRepository,
        PersonMiniGraphBuilder $personMiniGraphBuilder,
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

        $person = $personRepository->findBySlug($slug);
        if (!$person instanceof Person || Person::STATUS_APPROVED !== $person->getStatus()) {
            throw new NotFoundHttpException('Personne introuvable.');
        }

        $payload = $personMiniGraphBuilder->build($person);
        $response = new JsonResponse($payload);
        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }
}
