<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller;

use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Repository\PersonRepository;
use App\Shared\I18n\LocalizedContentResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizationShowController extends AbstractController
{
    #[Route('/organizations/{slug}', name: 'app_organization_show', methods: ['GET'])]
    public function __invoke(
        Request $request,
        string $slug,
        OrganizationRepository $organizationRepository,
        PersonRepository $personRepository,
        LocalizedContentResolver $localizedContentResolver,
    ): Response {
        $organization = $organizationRepository->findOneBy(['slug' => $slug]);
        if (!$organization instanceof Organization || 'approved' !== $organization->getStatus()) {
            throw new NotFoundHttpException('Organisation introuvable.');
        }

        $locale = $request->getLocale();
        $description = $localizedContentResolver->resolveOrganizationDescription($organization, $locale);
        $displayName = $localizedContentResolver->resolveOrganizationDisplayName($organization, $locale);
        $officialName = $organization->getOfficialName();
        $showOfficialSubtitle = 0 !== strcasecmp(trim($officialName), trim($displayName));

        $memberCount = $personRepository->countApprovedMembersForOrganization($organization, null);

        $response = $this->render('catalog/organization/show.html.twig', [
            'organization' => $organization,
            'organization_display_name' => $displayName,
            'organization_show_official_subtitle' => $showOfficialSubtitle,
            'organization_official_name' => $officialName,
            'catalog_description' => $description,
            'organization_member_count' => $memberCount,
        ]);

        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
