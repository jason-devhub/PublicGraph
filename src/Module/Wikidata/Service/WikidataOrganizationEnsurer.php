<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Service;

use App\Module\Catalog\Entity\Country;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Wikidata\Client\WikidataSparqlClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Intl\Countries;

/**
 * Crée une Organisation locale à partir d’un item Wikidata si elle n’existe pas encore.
 */
final class WikidataOrganizationEnsurer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationRepository $organizationRepository,
        private readonly WikidataSparqlClient $sparqlClient,
        private readonly WikidataOrganizationMapper $organizationMapper,
    ) {
    }

    /**
     * @throws \InvalidArgumentException QID invalide
     * @throws \RuntimeException         item absent ou SPARQL sans résultat
     */
    public function ensure(string $orgQid, string $defaultCountryIso = 'FR'): Organization
    {
        $orgQid = strtoupper(trim($orgQid));
        if (!preg_match('/^Q\d+$/', $orgQid)) {
            throw new \InvalidArgumentException('QID organisation invalide : '.$orgQid);
        }
        $existing = $this->organizationRepository->findOneByWikidataId($orgQid);
        if ($existing instanceof Organization) {
            return $existing;
        }
        $binding = $this->sparqlClient->findOrganizationBindingByQid($orgQid);
        if (null === $binding) {
            throw new \RuntimeException('Organisation introuvable sur Wikidata (SPARQL vide) : '.$orgQid);
        }
        $dto = $this->organizationMapper->map($binding);
        $o = new Organization();
        $o->setOfficialName($dto->officialName);
        $o->setType($dto->organizationType);
        $o->setStatus(Organization::STATUS_APPROVED);
        $o->setWikidataId(strtoupper($dto->wikidataId));
        $country = $this->ensureCountryFromIso(strtoupper($defaultCountryIso));
        if ($country instanceof Country) {
            $o->addCountry($country);
        }
        $this->entityManager->persist($o);
        $this->entityManager->flush();

        return $o;
    }

    private function ensureCountryFromIso(string $iso): ?Country
    {
        $iso = strtoupper($iso);
        $country = $this->entityManager->find(Country::class, $iso);
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
        $this->entityManager->persist($country);

        return $country;
    }
}
