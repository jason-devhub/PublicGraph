<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Person\Entity\PersonTranslation;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Jeu de données minimal pour les pages catalogue et parcours visiteur (tests).
 */
final class CatalogPublicFixture
{
    public static function seed(EntityManagerInterface $em): void
    {
        $country = $em->find(Country::class, 'FR');
        if (!$country instanceof Country) {
            $country = new Country('FR', 'France', 'France', 'EU');
            $em->persist($country);
        }

        $org = $em->getRepository(Organization::class)->findOneBy(['officialName' => 'WEF Test']);
        if (!$org instanceof Organization) {
            $org = new Organization();
            $org->setOfficialName('WEF Test');
            $org->setType(Organization::TYPE_INFLUENCE_NETWORK);
            $org->setStatus(Organization::STATUS_APPROVED);
            $org->addCountry($country);
            $em->persist($org);
            $em->flush();
        }

        $approved = $em->getRepository(Person::class)->findOneBy(['givenName' => 'Jean', 'familyName' => 'Public']);
        if (!$approved instanceof Person) {
            $approved = new Person();
            $approved->setGivenName('Jean');
            $approved->setFamilyName('Public');
            $approved->setStatus(Person::STATUS_APPROVED);
            $approved->setRoleCategories(['politician']);
            $approved->addNationality($country);
            $tr = new PersonTranslation();
            $tr->setLocale('fr');
            $tr->setDescription('Description de test.');
            $approved->addTranslation($tr);
            $em->persist($approved);
            $em->flush();

            $membership = new Membership();
            $membership->setPerson($approved);
            $membership->setOrganization($org);
            $membership->setYear(2020);
            $membership->setStatus('approved');
            $em->persist($membership);
            $em->flush();

            $source = new Source();
            $source->setUrl('https://example.org/source-wef');
            $source->setTitle('Source test');
            $source->setType(Source::TYPE_OTHER);
            $source->setDomain('example.org');
            $em->persist($source);
            $em->flush();

            $link = new EntitySource();
            $link->setSource($source);
            $link->setEntityType(EntitySource::ENTITY_MEMBERSHIP);
            $link->setEntityId((int) $membership->getId());
            $em->persist($link);
        }

        $pending = $em->getRepository(Person::class)->findOneBy(['givenName' => 'Pending', 'familyName' => 'Face']);
        if (!$pending instanceof Person) {
            $pending = new Person();
            $pending->setGivenName('Pending');
            $pending->setFamilyName('Face');
            $pending->setStatus(Person::STATUS_PENDING);
            $pending->setRoleCategories(['other_influencer']);
            $em->persist($pending);
        }

        $em->flush();

        $em->createQueryBuilder()
            ->update(Person::class, 'p')
            ->set('p.slug', ':slug')
            ->where('p.givenName = :gn AND p.familyName = :fn')
            ->setParameter('slug', 'jean-public')
            ->setParameter('gn', 'Jean')
            ->setParameter('fn', 'Public')
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->update(Person::class, 'p')
            ->set('p.slug', ':slug')
            ->where('p.givenName = :gn AND p.familyName = :fn')
            ->setParameter('slug', 'pending-face')
            ->setParameter('gn', 'Pending')
            ->setParameter('fn', 'Face')
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->update(Organization::class, 'o')
            ->set('o.slug', ':slug')
            ->where('o.officialName = :name')
            ->setParameter('slug', 'wef-test')
            ->setParameter('name', 'WEF Test')
            ->getQuery()
            ->execute();

        for ($i = 0; $i < 28; ++$i) {
            $exists = $em->getRepository(Person::class)->findOneBy(['familyName' => 'CatalogBulk-'.$i]);
            if ($exists instanceof Person) {
                continue;
            }
            $bulk = new Person();
            $bulk->setGivenName('Bulk');
            $bulk->setFamilyName('CatalogBulk-'.$i);
            $bulk->setStatus(Person::STATUS_APPROVED);
            $bulk->setRoleCategories(['politician']);
            $bulk->addNationality($country);
            $em->persist($bulk);
        }

        $em->flush();

        $em->clear();
    }
}
