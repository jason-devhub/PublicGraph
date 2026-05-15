<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\Party;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\SourceRepository;
use App\Module\Source\Service\EntitySourceManager;
use App\Module\User\Entity\User;
use App\Module\Wikidata\Dto\OrganizationDto;
use App\Module\Wikidata\Dto\PersonDto;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Intl\Countries;

/**
 * Import ou mise à jour d’une Person depuis un PersonDto (flux Wikidata).
 */
final class WikidataPersonImporter
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntitySourceManager $entitySourceManager,
    ) {
    }

    /**
     * @param ?string $syncNationalityIso Code ISO-2 du filtre de sync (ex. FR) : si le DTO n’a pas de nationalités WD, on applique ce pays.
     *
     * @return array{created: bool, updated: bool, skipped: bool}
     */
    public function importFromDto(PersonDto $dto, bool $force, bool $dryRun, ?User $systemUser, ?string $syncNationalityIso = null): array
    {
        $existing = $this->doctrine->getRepository(Person::class)->findOneBy(['wikidataId' => strtoupper(trim($dto->wikidataId))]);
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
        $nationalityIsos = $dto->nationalityIsoCodes;
        if ([] === $nationalityIsos && null !== $syncNationalityIso && 2 === \strlen($syncNationalityIso) && ctype_alpha($syncNationalityIso)) {
            $nationalityIsos = [strtoupper($syncNationalityIso)];
        }
        $this->syncNationalities($person, $nationalityIsos);

        if (null === $person->getId()) {
            $this->em()->persist($person);
        }
        $this->em()->flush();

        $this->syncMembershipsAndSources($person, $dto, $source, $systemUser);
        $this->syncPositions($person, $dto, $source, $systemUser);

        $person->setLastWikidataSyncAt(new \DateTimeImmutable());
        $this->em()->flush();

        return [
            'created' => null === $existing,
            'updated' => null !== $existing,
            'skipped' => false,
        ];
    }

    /**
     * Ajoute ou met à jour une adhésion annuelle à une organisation cible, sourcée par l’item Wikidata de l’édition (conférence, etc.).
     *
     * Utile lorsque {@see importFromDto} a été ignoré (`skipped` sans --force) : l’adhésion événementielle est quand même enregistrée.
     */
    public function ensureParticipationMembershipFromEvent(
        string $personWikidataId,
        string $organizationWikidataQid,
        int $year,
        string $eventQid,
        bool $dryRun,
        ?User $systemUser,
    ): void {
        if ($dryRun) {
            return;
        }
        $personWd = strtoupper(trim($personWikidataId));
        $orgWd = strtoupper(trim($organizationWikidataQid));
        $eventWd = strtoupper(trim($eventQid));
        if (!preg_match('/^Q\d+$/', $personWd) || !preg_match('/^Q\d+$/', $orgWd) || !preg_match('/^Q\d+$/', $eventWd)) {
            return;
        }
        $person = $this->doctrine->getRepository(Person::class)->findOneBy(['wikidataId' => $personWd]);
        if (!$person instanceof Person) {
            return;
        }
        $org = $this->doctrine->getRepository(Organization::class)->findOneBy(['wikidataId' => $orgWd]);
        if (!$org instanceof Organization) {
            return;
        }
        $eventUrl = 'https://www.wikidata.org/wiki/'.$eventWd;
        $source = $this->getOrCreateWikidataSource($eventUrl);
        $this->upsertMembership($person, $org, $year, $source, $systemUser);
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

    /** @param list<string> $countryIsoAlpha2 */
    private function syncNationalities(Person $person, array $countryIsoAlpha2): void
    {
        if ($person->isFieldManuallyEditedForWikidata('nationalities')) {
            return;
        }
        foreach ($person->getNationalities()->toArray() as $c) {
            $person->getNationalities()->removeElement($c);
        }
        foreach ($countryIsoAlpha2 as $iso) {
            $iso = strtoupper(trim((string) $iso));
            if (2 !== \strlen($iso) || !ctype_alpha($iso)) {
                continue;
            }
            $country = $this->ensureCountryFromIso($iso);
            if ($country instanceof Country) {
                $person->addNationality($country);
            }
        }
    }

    private function ensureCountryFromIso(string $iso): ?Country
    {
        $iso = strtoupper($iso);
        $country = $this->em()->find(Country::class, $iso);
        if ($country instanceof Country) {
            return $country;
        }
        try {
            $namesFr = Countries::getNames('fr');
        } catch (\Throwable) {
            return null;
        }
        if (!isset($namesFr[$iso])) {
            return null;
        }
        $country = new Country(
            $iso,
            Countries::getName($iso, 'fr'),
            Countries::getName($iso, 'en'),
            'Unknown',
        );
        $this->em()->persist($country);

        return $country;
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
        $this->em()->persist($m);
        $this->em()->flush();
        $this->ensureEntitySource($m, $source, $systemUser);
    }

    private function ensureEntitySource(Membership $m, Source $source, ?User $systemUser): void
    {
        $id = $m->getId();
        if (null === $id) {
            return;
        }
        $repo = $this->em()->getRepository(EntitySource::class);
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
        $repo = $this->em()->getRepository(EntitySource::class);
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
                    && $existing->getStartDate()->format('Y-m-d') === $start->format('Y-m-d')) {
                    $existing->setTitleFr($title);
                    $existing->setEndDate($end);
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
            $this->em()->persist($p);
            $this->em()->flush();
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
        $existing = $this->doctrine->getRepository(Organization::class)->findOneBy(['wikidataId' => strtoupper(trim($dto->wikidataId))]);
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
            $fr = $this->ensureCountryFromIso('FR');
            if ($fr instanceof Country) {
                $o->addCountry($fr);
            }
        }
        $this->em()->persist($o);
        $this->em()->flush();
        if (Organization::TYPE_POLITICAL_PARTY === $dto->organizationType && null === $o->getPartyDetails()) {
            $party = new Party();
            $party->setOrganization($o);
            $this->em()->persist($party);
            $this->em()->flush();
        }

        return $o;
    }

    private function getOrCreateWikidataSource(string $url): Source
    {
        $repo = $this->doctrine->getRepository(Source::class);
        if (!$repo instanceof SourceRepository) {
            throw new \LogicException('SourceRepository attendu pour les sources Wikidata.');
        }
        $found = $repo->findOneWikidataItem($url);
        if ($found instanceof Source) {
            return $found;
        }
        $s = new Source();
        $s->setUrl($url);
        $s->setTitle('Wikidata');
        $s->setType(Source::TYPE_WIKIDATA);
        $s->setAccessedAt(new \DateTimeImmutable('today'));
        $this->em()->persist($s);
        $this->em()->flush();

        return $s;
    }

    private function em(): EntityManagerInterface
    {
        $m = $this->doctrine->getManager();
        if (!$m instanceof EntityManagerInterface) {
            throw new \LogicException('ORM EntityManager attendu pour WikidataPersonImporter.');
        }

        return $m;
    }
}
