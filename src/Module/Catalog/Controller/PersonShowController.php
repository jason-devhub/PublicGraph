<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller;

use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use App\Module\Proximity\Service\CoPresenceFinder;
use App\Module\Source\Repository\EntitySourceRepository;
use App\Shared\I18n\LocalizedContentResolver;
use App\Shared\Seo\SafeJsonLdEncoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PersonShowController extends AbstractController
{
    #[Route('/people/{slug}', name: 'app_person_show', methods: ['GET'])]
    public function __invoke(
        Request $request,
        string $slug,
        PersonRepository $personRepository,
        EntitySourceRepository $entitySourceRepository,
        PersonSimilarityRepository $personSimilarityRepository,
        CoPresenceFinder $coPresenceFinder,
        LocalizedContentResolver $localizedContentResolver,
    ): Response {
        $person = $personRepository->findBySlug($slug);
        if (!$person instanceof Person || Person::STATUS_APPROVED !== $person->getStatus()) {
            throw new NotFoundHttpException('Personne introuvable.');
        }

        $locale = $request->getLocale();
        $description = $localizedContentResolver->resolvePersonDescription($person, $locale);

        /** @var list<Membership> $approvedMemberships */
        $approvedMemberships = $person->getMemberships()->filter(static fn (Membership $m): bool => 'approved' === $m->getStatus())->getValues();

        /** @var array<int, list<Membership>> $membershipsByOrg */
        $membershipsByOrg = [];
        foreach ($approvedMemberships as $membership) {
            $org = $membership->getOrganization();
            $oid = $org?->getId();
            if (null === $oid) {
                continue;
            }
            $membershipsByOrg[$oid][] = $membership;
        }

        /** @var list<Position> $approvedPositions */
        $approvedPositions = $person->getPositions()->filter(static fn (Position $p): bool => 'approved' === $p->getStatus())->getValues();
        usort($approvedPositions, static fn (Position $a, Position $b): int => $b->getStartDate() <=> $a->getStartDate());

        $positionTitles = [];
        foreach ($approvedPositions as $pos) {
            $pid = $pos->getId();
            if (null !== $pid) {
                $positionTitles[$pid] = $localizedContentResolver->resolvePositionTitle($pos, $locale);
            }
        }

        /** @var list<RevolvingDoor> $approvedDoors */
        $approvedDoors = $person->getRevolvingDoors()->filter(static fn (RevolvingDoor $r): bool => 'approved' === $r->getStatus())->getValues();

        $sources = $entitySourceRepository->findDistinctSourcesLinkedToPerson($person);

        $similarProfiles = $personSimilarityRepository->findTopForPerson($person, 10);
        $copresences = $coPresenceFinder->findFor($person);

        $organizationDisplayNames = [];
        foreach ($approvedMemberships as $m) {
            $o = $m->getOrganization();
            if ($o instanceof Organization && null !== $o->getId()) {
                $organizationDisplayNames[$o->getId()] = $localizedContentResolver->resolveOrganizationDisplayName($o, $locale);
            }
        }

        $memberOfNames = [];
        foreach ($approvedMemberships as $m) {
            $o = $m->getOrganization();
            if (null !== $o) {
                $memberOfNames[] = $localizedContentResolver->resolveOrganizationDisplayName($o, $locale);
            }
        }
        $memberOfNames = array_values(array_unique($memberOfNames));

        $copresenceOrganizationNames = [];
        foreach ($copresences as $row) {
            $o = $row['organization'];
            if ($o instanceof Organization && null !== $o->getId()) {
                $copresenceOrganizationNames[$o->getId()] = $localizedContentResolver->resolveOrganizationDisplayName($o, $locale);
            }
        }

        $revolvingDoorRows = [];
        foreach ($approvedDoors as $rd) {
            $sp = $rd->getSourcePosition();
            $tp = $rd->getTargetPosition();
            $revolvingDoorRows[] = [
                'door' => $rd,
                'factual_note' => $localizedContentResolver->resolveRevolvingDoorFactualNote($rd, $locale),
                'source_title' => $localizedContentResolver->resolvePositionTitle($sp, $locale),
                'target_title' => $localizedContentResolver->resolvePositionTitle($tp, $locale),
                'source_org' => $localizedContentResolver->resolveOrganizationDisplayName($sp->getOrganization(), $locale),
                'target_org' => $localizedContentResolver->resolveOrganizationDisplayName($tp->getOrganization(), $locale),
            ];
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => trim($person->getUsageName() ?: $person->getGivenName().' '.$person->getFamilyName()),
        ];
        $birthDate = $person->getBirthDate();
        if (null !== $birthDate) {
            $jsonLd['birthDate'] = $birthDate->format('Y-m-d');
        }
        $nationalityCodes = [];
        foreach ($person->getNationalities() as $country) {
            $nationalityCodes[] = $country->getIsoCode();
        }
        if ([] !== $nationalityCodes) {
            $jsonLd['nationality'] = $nationalityCodes;
        }
        if ([] !== $memberOfNames) {
            $jsonLd['memberOf'] = array_map(
                static fn (string $name): array => ['@type' => 'Organization', 'name' => $name],
                $memberOfNames,
            );
        }

        $personJsonLd = SafeJsonLdEncoder::encodeArray($jsonLd);

        $response = $this->render('catalog/person/show.html.twig', [
            'person' => $person,
            'catalog_description' => $description,
            'memberships_by_org' => $membershipsByOrg,
            'positions' => $approvedPositions,
            'position_titles' => $positionTitles,
            'revolving_door_rows' => $revolvingDoorRows,
            'sources' => $sources,
            'person_json_ld' => $personJsonLd,
            'similar_profiles' => $similarProfiles,
            'copresences' => $copresences,
            'copresence_organization_names' => $copresenceOrganizationNames,
            'organization_display_names' => $organizationDisplayNames,
        ]);

        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}
