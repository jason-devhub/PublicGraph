<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\EntitySourceFactory;
use App\Factory\MembershipFactory;
use App\Factory\OrganizationFactory;
use App\Factory\PartyFactory;
use App\Factory\PersonFactory;
use App\Factory\PositionFactory;
use App\Factory\SourceFactory;
use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class MinimalFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $fr = $manager->getRepository(Country::class)->find('FR');
        if (!$fr instanceof Country) {
            throw new \RuntimeException('Le pays FR doit être chargé avant MinimalFixtures.');
        }

        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        if (!$admin instanceof User) {
            throw new \RuntimeException("L'utilisateur admin@example.com doit exister (UserFixtures).");
        }

        $influence = OrganizationFactory::createOne([
            'officialName' => 'Réseau d’influence (test)',
            'type' => Organization::TYPE_INFLUENCE_NETWORK,
            'status' => 'approved',
        ]);
        $influence->addCountry($fr);

        $partyOrg = OrganizationFactory::createOne([
            'officialName' => 'Parti de test',
            'type' => Organization::TYPE_POLITICAL_PARTY,
            'status' => 'approved',
        ]);
        $partyOrg->addCountry($fr);
        PartyFactory::createOne(['organization' => $partyOrg]);

        $corp = OrganizationFactory::createOne([
            'officialName' => 'Entreprise de test',
            'type' => Organization::TYPE_CORPORATION,
            'status' => 'approved',
        ]);
        $corp->addCountry($fr);

        $manager->flush();

        $persons = [];
        for ($i = 0; $i < 5; ++$i) {
            $p = PersonFactory::createOne([
                'givenName' => 'Test',
                'familyName' => 'Personne-'.$i,
                'status' => Person::STATUS_APPROVED,
                'createdBy' => $admin,
                'roleCategories' => ['politician'],
            ]);
            $p->addNationality($fr);
            $persons[] = $p;
        }

        $manager->flush();

        $orgs = [$influence, $partyOrg, $corp];
        $memberships = [];
        foreach ($persons as $i => $person) {
            $org = $orgs[$i % 3];
            $memberships[] = MembershipFactory::createOne([
                'person' => $person,
                'organization' => $org,
                'year' => 2020 + $i,
                'status' => 'approved',
                'createdBy' => $admin,
            ]);
        }

        PositionFactory::createOne([
            'person' => $persons[0],
            'organization' => $partyOrg,
            'titleFr' => 'Mandat électif (test)',
            'nature' => Position::NATURE_ELECTED_OFFICE,
            'startDate' => new \DateTimeImmutable('2020-01-01'),
            'endDate' => new \DateTimeImmutable('2024-06-30'),
            'country' => $fr,
            'status' => 'approved',
            'createdBy' => $admin,
        ]);

        $manager->flush();

        $seq = 0;
        foreach ($memberships as $m) {
            $id = $m->getId();
            if (null !== $id) {
                EntitySourceFactory::createOne([
                    'source' => SourceFactory::createOne([
                        'url' => \sprintf('https://fixture.publicgraph.test/minimal/membership/%d/%d', $id, $seq),
                        'title' => 'Source minimal membership '.$id,
                    ]),
                    'entityType' => EntitySource::ENTITY_MEMBERSHIP,
                    'entityId' => $id,
                    'addedBy' => $admin,
                ]);
                ++$seq;
            }
        }

        $position = $manager->getRepository(Position::class)->findOneBy([
            'person' => $persons[0],
        ]);
        if (null !== $position && null !== $position->getId()) {
            EntitySourceFactory::createOne([
                'source' => SourceFactory::createOne([
                    'url' => \sprintf('https://fixture.publicgraph.test/minimal/position/%d', $position->getId()),
                    'title' => 'Source minimal position',
                ]),
                'entityType' => EntitySource::ENTITY_POSITION,
                'entityId' => $position->getId(),
                'addedBy' => $admin,
            ]);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CountryFixtures::class, UserFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
