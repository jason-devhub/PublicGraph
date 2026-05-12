<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller;

use App\Module\Legislation\Repository\RevolvingDoorRepository;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Repository\PersonRepository;
use App\Shared\Service\CronTelemetry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusController extends AbstractController
{
    #[Route('/status', name: 'app_status', methods: ['GET'])]
    public function __invoke(
        PersonRepository $personRepository,
        OrganizationRepository $organizationRepository,
        RevolvingDoorRepository $revolvingDoorRepository,
        CronTelemetry $cronTelemetry,
        #[Autowire('%kernel.environment%')]
        string $kernelEnvironment,
    ): Response {
        $exposeCronTelemetry = 'prod' !== $kernelEnvironment || filter_var(
            $_ENV['STATUS_SHOW_TELEMETRY'] ?? getenv('STATUS_SHOW_TELEMETRY') ?: '0',
            FILTER_VALIDATE_BOOLEAN,
        );
        $telemetry = $exposeCronTelemetry ? $cronTelemetry->readAll() : [];

        $persons = $personRepository->countApprovedPublic();
        $organizations = $organizationRepository->countApprovedPublic();
        $doors = $revolvingDoorRepository->countApproved();

        $health = $this->resolveHealth($telemetry);

        $response = $this->render('catalog/status.html.twig', [
            'person_count' => $persons,
            'organization_count' => $organizations,
            'revolving_door_count' => $doors,
            'telemetry' => $telemetry,
            'show_cron_telemetry' => $exposeCronTelemetry,
            'health' => $health,
        ]);

        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }

    /**
     * @param array<string, array{lastSuccessAt?: string}> $telemetry
     */
    private function resolveHealth(array $telemetry): string
    {
        $entry = $telemetry['app:sources:check-urls'] ?? [];
        $last = \is_array($entry) ? ($entry['lastSuccessAt'] ?? null) : null;
        $sourcesAt = $this->parseIso(\is_string($last) ? $last : null);
        if (null === $sourcesAt || $sourcesAt < new \DateTimeImmutable('-21 days')) {
            return 'degraded';
        }

        return 'ok';
    }

    private function parseIso(?string $iso): ?\DateTimeImmutable
    {
        if (null === $iso || '' === $iso) {
            return null;
        }

        try {
            return new \DateTimeImmutable($iso);
        } catch (\Exception) {
            return null;
        }
    }
}
