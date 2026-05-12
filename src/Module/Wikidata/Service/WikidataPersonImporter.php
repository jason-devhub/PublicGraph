<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\Party;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\SourceRepository;
use App\Module\Source\Service\EntitySourceManager;
use App\Module\User\Entity\User;
use App\Module\Wikidata\Client\WikidataCountryQids;
use App\Module\Wikidata\Dto\OrganizationDto;
use App\Module\Wikidata\Dto\PersonDto;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Import ou mise à jour d’une Person depuis un PersonDto (flux Wikidata).
 */
final class WikidataPersonImporter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PersonRepository $personRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly SourceRepository $sourceRepository,
        private readonly EntitySourceManager $entitySourceManager,
    ) {
    }

    /**
     * @return array{created: bool, updated: bool, skipped: bool}
     */
    public function importFromDto(PersonDto $dto, bool $force, bool $dryRun, ?User $systemUser): array
    {
        $existing = $this->personRepository->findOneByWikidataId($dto->wikidataId);
        if (null !== $existing && !$force) {
            return ['created' => false, 'updated' => false, 'skipped' => true];
        }

        if ($dryRun) {
            return [
                'created' => null === $existing,
                'updated' => null !== $existing,
                'skipped' => false,
            ];
        }

        $wikidataUrl = 'https://www.wikidata.org/wiki/'.$dto->wikidataId;
        $source = $this->getOrCreateWikidataSource($wikidataUrl);

        $person = $existing ?? new Person();
        if (null === $existing) {
            $person->setStatus(Person::STATUS_APPROVED);
            $person->setWikidataId(strtoupper($dto->wikidataId));
            if (null !== $systemUser) {
                $person->setCreatedBy($systemUser);
            }
        }

        $this->applyPersonScalarFields($person, $dto);
        $this->syncNationalities($person, $dto);

        if (null === $person->getId()) {
            $this->entityManager->persist($person);
        }
        $this->entityManager->flush();

        $this->syncMembershipsAndSources($person, $dto, $source, $systemUser);
        $this->syncPositions($person, $dto, $source, $systemUser);

        $person->setLastWikidataSyncAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return [
            'created' => null === $existing,
            'updated' => null !== $existing,
            'skipped' => false,
        ];
    }

    private function applyPersonScalarFields(Person $person, PersonDto $dto): void
    {
        if (!$person->isFieldManuallyEditedForWikidata('givenName')) {
            $person->setGivenName($dto->givenName);
        }
        if (!$person->isFieldManuallyEditedForWikidata('familyName')) {
            $person->setFamilyName($dto->familyName);
        }
        if (!$person->isFieldManuallyEditedForWikidata('birthDate')) {
            $person->setBirthDate($dto->birthDate);
        }
        if (!$person->isFieldManuallyEditedForWikidata('deathDate')) {
            $person->setDeathDate($dto->deathDate);
        }
        if (!$person->isFieldManuallyEditedForWikidata('gender')) {
            $person->setGender($dto->gender);
        }
        if (!$person->isFieldManuallyEditedForWikidata('photoUrl')) {
            $person->setPhotoUrl($dto->photoUrl);
        }
        if (!$person->isFieldManuallyEditedForWikidata('roleCategories')) {
            $person->setRoleCategories($dto->roleCategories);
        }
    }

    private function syncNationalities(Person $person, PersonDto $dto): void
    {
        if ($person->isFieldManuallyEditedForWikidata('nationalities')) {
            return;
        }
        foreach ($person->getNationalities()->toArray() as $c) {
            $person->getNationalities()->removeElement($c);
        }
        foreach ($dto->nationalityQids as $qid) {
            $iso = WikidataCountryQids::isoForNationalityQid($qid);
            if (null === $iso) {
                continue;
            }
            $country = $this->entityManager->find(Country::class, $iso);
            if ($country instanceof Country) {
                $person->addNationality($country);
            }
        }
    }

    private function syncMembershipsAndSources(Person $person, PersonDto $dto, Source $source, ?User $systemUser): void
    {
        foreach ($dto->partyMemberships as $row) {
            $org = $this->findOrCreateOrganizationFromWikidata(
                new OrganizationDto($row['qid'], $row['label'], Organization::TYPE_POLITICAL_PARTY),
                $person,
            );
            $this->upsertMembership($person, $org, null, $source, $systemUser);
        }
        foreach ($dto->organizationMemberships as $row) {
            $org = $this->findOrCreateOrganizationFromWikidata(
                new OrganizationDto($row['qid'], $row['label'], Organization::TYPE_INFLUENCE_NETWORK),
                $person,
            );
            $this->upsertMembership($person, $org, null, $source, $systemUser);
        }
    }

    private function upsertMembership(Person $person, Organization $org, ?int $year, Source $source, ?User $systemUser): void
    {
        foreach ($person->getMemberships() as $m) {
            if ($m->getOrganization()?->getId() === $org->getId() && $m->getYear() === $year) {
                $m->setStatus('approved');
                if (null !== $systemUser) {
                    $m->setCreatedBy($systemUser);
                }
                $this->ensureEntitySource($m, $source, $systemUser);

                return;
            }
        }
        $m = new Membership();
        $m->setPerson($person);
        $m->setOrganization($org);
        $m->setYear($year);
        $m->setStatus('approved');
        if (null !== $systemUser) {
            $m->setCreatedBy($systemUser);
        }
        $this->entityManager->persist($m);
        $this->entityManager->flush();
        $this->ensureEntitySource($m, $source, $systemUser);
    }

    private function ensureEntitySource(Membership $m, Source $source, ?User $systemUser): void
    {
        $id = $m->getId();
        if (null === $id) {
            return;
        }
        $repo = $this->entityManager->getRepository(EntitySource::class);
        $exists = $repo->findOneBy([
            'source' => $source,
            'entityType' => EntitySource::ENTITY_MEMBERSHIP,
            'entityId' => $id,
        ]);
        if (null !== $exists) {
            return;
        }
        $this->entitySourceManager->persistLink($source, EntitySource::ENTITY_MEMBERSHIP, $id, $systemUser);
    }

    private function ensurePositionSource(Position $p, Source $source, ?User $systemUser): void
    {
        $id = $p->getId();
        if (null === $id) {
            return;
        }
        $repo = $this->entityManager->getRepository(EntitySource::class);
        $exists = $repo->findOneBy([
            'source' => $source,
            'entityType' => EntitySource::ENTITY_POSITION,
            'entityId' => $id,
        ]);
        if (null !== $exists) {
            return;
        }
        $this->entitySourceManager->persistLink($source, EntitySource::ENTITY_POSITION, $id, $systemUser);
    }

    private function syncPositions(Person $person, PersonDto $dto, Source $source, ?User $systemUser): void
    {
        foreach ($dto->positionsHeld as $pos) {
            $start = $this->parseWikidataDate($pos['start'] ?? null);
            if (null === $start) {
                continue;
            }
            $end = $this->parseWikidataDate($pos['end'] ?? null);
            $orgDto = new OrganizationDto($pos['qid'], $pos['label'], Organization::TYPE_GOVERNMENT_BODY);
            $org = $this->findOrCreateOrganizationFromWikidata($orgDto, $person);
            $title = $pos['label'];
            $found = false;
            foreach ($person->getPositions() as $existing) {
                if ($existing->getOrganization()?->getId() === $org->getId()
                    && $existing->getTitleFr() === $title
                    && $existing->getStartDate()->format('Y-m-d') === $start->format('Y-m-d')) {
                    $existing->setStatus('approved');
                    $found = true;
                    $id = $existing->getId();
                    if (null !== $id) {
                        $this->ensurePositionSource($existing, $source, $systemUser);
                    }
                    break;
                }
            }
            if ($found) {
                continue;
            }
            $p = new Position();
            $p->setPerson($person);
            $p->setOrganization($org);
            $p->setTitleFr($title);
            $p->setNature(Position::NATURE_ELECTED_OFFICE);
            $p->setStartDate($start);
            $p->setEndDate($end);
            $p->setStatus('approved');
            if (null !== $systemUser) {
                $p->setCreatedBy($systemUser);
            }
            $this->entityManager->persist($p);
            $this->entityManager->flush();
            $this->ensurePositionSource($p, $source, $systemUser);
        }
    }

    private function parseWikidataDate(?string $raw): ?\DateTimeImmutable
    {
        if (null === $raw || '' === trim($raw)) {
            return null;
        }
        $raw = trim($raw);
        try {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
                return new \DateTimeImmutable($m[1].'-'.$m[2].'-'.$m[3]);
            }
            if (preg_match('/^(\d{4})$/', $raw, $m)) {
                return new \DateTimeImmutable($m[1].'-01-01');
            }
        } catch (\Exception) {
            return null;
        }

        return null;
    }

    private function findOrCreateOrganizationFromWikidata(OrganizationDto $dto, Person $person): Organization
    {
        $existing = $this->organizationRepository->findOneByWikidataId($dto->wikidataId);
        if ($existing instanceof Organization) {
            return $existing;
        }
        $o = new Organization();
        $o->setOfficialName($dto->officialName);
        $o->setType($dto->organizationType);
        $o->setStatus(Organization::STATUS_APPROVED);
        $o->setWikidataId(strtoupper($dto->wikidataId));
        foreach ($person->getNationalities() as $c) {
            $o->addCountry($c);
        }
        if ($o->getCountries()->isEmpty()) {
            $fr = $this->entityManager->find(Country::class, 'FR');
            if ($fr instanceof Country) {
                $o->addCountry($fr);
            }
        }
        $this->entityManager->persist($o);
        $this->entityManager->flush();
        if (Organization::TYPE_POLITICAL_PARTY === $dto->organizationType && null === $o->getPartyDetails()) {
            $party = new Party();
            $party->setOrganization($o);
            $this->entityManager->persist($party);
            $this->entityManager->flush();
        }

        return $o;
    }

    private function getOrCreateWikidataSource(string $url): Source
    {
        $found = $this->sourceRepository->findOneWikidataItem($url);
        if ($found instanceof Source) {
            return $found;
        }
        $s = new Source();
        $s->setUrl($url);
        $s->setTitle('Wikidata');
        $s->setType(Source::TYPE_WIKIDATA);
        $s->setAccessedAt(new \DateTimeImmutable('today'));
        $this->entityManager->persist($s);
        $this->entityManager->flush();

        return $s;
    }
}
