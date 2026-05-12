<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Legislation\Repository\RevolvingDoorRepository;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class HomeController extends AbstractController
{
    private const string CACHE_KEY_COUNTS = 'home.public_counts.v1';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly PersonRepository $personRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly RevolvingDoorRepository $revolvingDoorRepository,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(): Response
    {
        $counts = $this->cache->get(self::CACHE_KEY_COUNTS, function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return [
                'persons' => $this->personRepository->countApprovedPublic(),
                'organizations' => $this->organizationRepository->countApprovedPublic(),
                'revolving_doors' => $this->revolvingDoorRepository->countApproved(),
            ];
        });

        $recentPersons = $this->personRepository->findLatestApprovedPublic(3);
        $recentDoors = $this->revolvingDoorRepository->findLatestApprovedPublic(3);

        $response = $this->render('home/index.html.twig', [
            'counts' => $counts,
            'recentPersons' => $recentPersons,
            'recentDoors' => $recentDoors,
        ]);
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
