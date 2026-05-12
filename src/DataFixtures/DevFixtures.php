<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\EntitySourceFactory;
use App\Factory\LegislativeActionFactory;
use App\Factory\MembershipFactory;
use App\Factory\OrganizationFactory;
use App\Factory\PartyFactory;
use App\Factory\PersonFactory;
use App\Factory\PositionFactory;
use App\Factory\RevolvingDoorFactory;
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

final class DevFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $countryRepo = $manager->getRepository(Country::class);
        $fr = $countryRepo->find('FR');
        $us = $countryRepo->find('US');
        $de = $countryRepo->find('DE');
        $gb = $countryRepo->find('GB');
        $ch = $countryRepo->find('CH');
        $nl = $countryRepo->find('NL');
        $be = $countryRepo->find('BE');
        $es = $countryRepo->find('ES');
        $it = $countryRepo->find('IT');

        if (!$fr instanceof Country || !$us instanceof Country) {
            throw new \RuntimeException('Les pays FR et US doivent être chargés avant DevFixtures (CountryFixtures).');
        }

        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        if (!$admin instanceof User) {
            throw new \RuntimeException("L'utilisateur admin@example.com doit exister (UserFixtures).");
        }

        $faker = \Faker\Factory::create('fr_FR');

        $influenceSpecs = [
            ['name' => 'Réunions du Groupe Bilderberg', 'countries' => [$nl, $be]],
            ['name' => 'Forum économique mondial', 'countries' => [$ch]],
            ['name' => 'Commission trilatérale', 'countries' => [$us]],
            ['name' => 'Bohemian Club', 'countries' => [$us]],
            ['name' => 'Council on Foreign Relations', 'countries' => [$us]],
        ];

        $influenceOrgs = [];
        foreach ($influenceSpecs as $spec) {
            $o = OrganizationFactory::createOne([
                'officialName' => $spec['name'],
                'type' => Organization::TYPE_INFLUENCE_NETWORK,
                'status' => 'approved',
            ]);
            foreach ($spec['countries'] as $c) {
                if ($c instanceof Country) {
                    $o->addCountry($c);
                }
            }
            $influenceOrgs[] = $o;
        }

        $partyNames = [
            'Renaissance',
            'Les Républicains',
            'Parti socialiste',
            'Europe Écologie Les Verts',
            'Rassemblement national',
        ];

        $partyOrgs = [];
        foreach ($partyNames as $name) {
            $o = OrganizationFactory::createOne([
                'officialName' => $name,
                'type' => Organization::TYPE_POLITICAL_PARTY,
                'status' => 'approved',
            ]);
            $o->addCountry($fr);
            PartyFactory::createOne(['organization' => $o]);
            $partyOrgs[] = $o;
        }

        $corpNames = ['TotalEnergies SE', 'Orange SA', 'BNP Paribas SA', 'Saint-Gobain SA', 'Airbus SE'];
        $corpOrgs = [];
        foreach ($corpNames as $name) {
            $o = OrganizationFactory::createOne([
                'officialName' => $name,
                'type' => Organization::TYPE_CORPORATION,
                'status' => 'approved',
            ]);
            $o->addCountry($fr);
            $corpOrgs[] = $o;
        }

        $otherSpecs = [
            ['name' => 'Groupe Canal+', 'type' => Organization::TYPE_MEDIA_GROUP, 'countries' => [$fr]],
            ['name' => 'Institut Montaigne', 'type' => Organization::TYPE_THINK_TANK, 'countries' => [$fr]],
            ['name' => 'Medef', 'type' => Organization::TYPE_LOBBY_GROUP, 'countries' => [$fr]],
            ['name' => 'Union européenne', 'type' => Organization::TYPE_INTERNATIONAL_BODY, 'countries' => [$fr, $de]],
            ['name' => 'Assemblée nationale', 'type' => Organization::TYPE_GOVERNMENT_BODY, 'countries' => [$fr]],
        ];

        $otherOrgs = [];
        foreach ($otherSpecs as $spec) {
            $o = OrganizationFactory::createOne([
                'officialName' => $spec['name'],
                'type' => $spec['type'],
                'status' => 'approved',
            ]);
            foreach ($spec['countries'] as $c) {
                if ($c instanceof Country) {
                    $o->addCountry($c);
                }
            }
            $otherOrgs[] = $o;
        }

        $governmentBody = $otherOrgs[4];

        $persons = [];
        for ($i = 0; $i < 30; ++$i) {
            $p = PersonFactory::createOne([
                'givenName' => $faker->firstName(),
                'familyName' => 'Français-'.$i,
                'status' => Person::STATUS_APPROVED,
                'createdBy' => $admin,
                'roleCategories' => 0 === $i % 2 ? ['politician'] : ['politician', 'civil_servant'],
            ]);
            $p->addNationality($fr);
            $persons[] = $p;
        }
        for ($i = 0; $i < 10; ++$i) {
            $p = PersonFactory::createOne([
                'givenName' => $faker->firstName(),
                'familyName' => 'American-'.$i,
                'status' => Person::STATUS_APPROVED,
                'createdBy' => $admin,
                'roleCategories' => ['business_leader'],
            ]);
            $p->addNationality($us);
            $persons[] = $p;
        }
        $otherCountries = array_values(array_filter([$de, $gb, $es, $it, $ch]));
        for ($i = 0; $i < 10; ++$i) {
            $nat = $otherCountries[$i % \count($otherCountries)];
            $p = PersonFactory::createOne([
                'givenName' => $faker->firstName(),
                'familyName' => 'International-'.$i,
                'status' => Person::STATUS_APPROVED,
                'createdBy' => $admin,
                'roleCategories' => ['other_influencer'],
            ]);
            if ($nat instanceof Country) {
                $p->addNationality($nat);
            }
            $persons[] = $p;
        }

        $manager->flush();

        $membershipTargets = [...$influenceOrgs, ...$partyOrgs, ...\array_slice($corpOrgs, 0, 3)];
        $memberships = [];
        for ($i = 0; $i < 100; ++$i) {
            $person = $persons[$i % 50];
            $org = $membershipTargets[$i % \count($membershipTargets)];
            $m = MembershipFactory::createOne([
                'person' => $person,
                'organization' => $org,
                'year' => 2018 + ($i % 8),
                'status' => 'approved',
                'createdBy' => $admin,
                'roleInOrganization' => 0 === $i % 3 ? 'membre' : null,
            ]);
            $memberships[] = $m;
        }

        $positionOrgs = [...$partyOrgs, $governmentBody, ...$corpOrgs, ...\array_slice($influenceOrgs, 0, 2)];
        $positions = [];
        for ($i = 0; $i < 70; ++$i) {
            $person = $persons[$i % 50];
            $org = $positionOrgs[$i % \count($positionOrgs)];
            $start = new \DateTimeImmutable(\sprintf('2015-%02d-01', ($i % 12) + 1));
            $end = 0 === $i % 4 ? $start->modify('+4 years') : null;
            $natures = [
                Position::NATURE_ELECTED_OFFICE,
                Position::NATURE_APPOINTED_OFFICE,
                Position::NATURE_CORPORATE_POSITION,
                Position::NATURE_BOARD_MEMBER,
            ];
            $pos = PositionFactory::createOne([
                'person' => $person,
                'organization' => $org,
                'titleFr' => 0 === $i % 5 ? 'Député' : (0 === $i % 3 ? 'Conseiller' : 'Administrateur'),
                'nature' => $natures[$i % \count($natures)],
                'startDate' => $start,
                'endDate' => $end,
                'country' => $fr,
                'status' => 'approved',
                'createdBy' => $admin,
            ]);
            $positions[] = $pos;
        }

        $legislativeTypes = ['law_authored', 'vote', 'decree_signed', 'amendment', 'policy_decision'];
        $legislativeActions = [];
        for ($i = 0; $i < 10; ++$i) {
            $author = $persons[$i % 50];
            $authorId = $author->getId();
            $ctx = null;
            if (null !== $authorId) {
                foreach ($positions as $candidate) {
                    if ($candidate->getPerson()?->getId() === $authorId) {
                        $ctx = $candidate;
                        break;
                    }
                }
            }
            $la = LegislativeActionFactory::createOne([
                'author' => $author,
                'contextualPosition' => $ctx,
                'type' => $legislativeTypes[$i % \count($legislativeTypes)],
                'actionDate' => new \DateTimeImmutable('2022-06-15'),
                'titleFr' => 'Action législative fixture '.$i,
                'descriptionFr' => 'Description factuelle de l\'action législative '.$i.'.',
                'status' => 'approved',
                'createdBy' => $admin,
            ]);
            if (0 === $i % 2 && isset($corpOrgs[0])) {
                $la->addBeneficiaryOrganization($corpOrgs[0]);
            }
            $legislativeActions[] = $la;
        }

        $revolvingDoors = [];
        for ($i = 0; $i < 5; ++$i) {
            $person = $persons[5 + $i * 8];
            $sourcePos = PositionFactory::createOne([
                'person' => $person,
                'organization' => $governmentBody,
                'titleFr' => 'Ministre délégué (fixture)',
                'nature' => Position::NATURE_APPOINTED_OFFICE,
                'startDate' => new \DateTimeImmutable('2018-01-01'),
                'endDate' => new \DateTimeImmutable('2020-06-30'),
                'country' => $fr,
                'status' => 'approved',
                'createdBy' => $admin,
            ]);
            $targetPos = PositionFactory::createOne([
                'person' => $person,
                'organization' => $corpOrgs[$i % \count($corpOrgs)],
                'titleFr' => 'Membre du comité exécutif (fixture)',
                'nature' => Position::NATURE_CORPORATE_POSITION,
                'startDate' => new \DateTimeImmutable('2021-01-15'),
                'endDate' => null,
                'country' => $fr,
                'status' => 'approved',
                'createdBy' => $admin,
            ]);
            $linking = null;
            $personId = $person->getId();
            if (null !== $personId) {
                foreach ($legislativeActions as $candidate) {
                    if ($candidate->getAuthor()?->getId() === $personId) {
                        $linking = $candidate;
                        break;
                    }
                }
            }
            $linking ??= $legislativeActions[$i % 10];
            $rd = RevolvingDoorFactory::createOne([
                'person' => $person,
                'sourcePosition' => $sourcePos,
                'targetPosition' => $targetPos,
                'linkingAction' => $linking,
                'factualNoteFr' => \sprintf(
                    'Chronologie : fin du mandat public le %s, début du mandat privé le %s.',
                    $sourcePos->getEndDate()?->format('Y-m-d') ?? '',
                    $targetPos->getStartDate()->format('Y-m-d')
                ),
                'status' => 'approved',
                'createdBy' => $admin,
            ]);
            $revolvingDoors[] = $rd;
        }

        $manager->flush();

        $seq = 0;
        foreach ($memberships as $m) {
            $id = $m->getId();
            if (null !== $id) {
                $this->linkEntitySource($admin, EntitySource::ENTITY_MEMBERSHIP, $id, $seq++);
            }
        }
        foreach ($positions as $p) {
            $id = $p->getId();
            if (null !== $id) {
                $this->linkEntitySource($admin, EntitySource::ENTITY_POSITION, $id, $seq++);
            }
        }
        foreach ($legislativeActions as $la) {
            $id = $la->getId();
            if (null !== $id) {
                $this->linkEntitySource($admin, EntitySource::ENTITY_LEGISLATIVE_ACTION, $id, $seq++);
            }
        }
        foreach ($revolvingDoors as $rd) {
            $id = $rd->getId();
            if (null !== $id) {
                $this->linkEntitySource($admin, EntitySource::ENTITY_REVOLVING_DOOR, $id, $seq++);
            }
        }

        $manager->flush();
    }

    private function linkEntitySource(User $addedBy, string $entityType, int $entityId, int $seq): void
    {
        EntitySourceFactory::createOne([
            'source' => SourceFactory::createOne([
                'url' => \sprintf('https://fixture.publicgraph.test/%s/%d/%d', $entityType, $entityId, $seq),
                'title' => 'Source documentaire '.$entityType.' #'.$entityId,
            ]),
            'entityType' => $entityType,
            'entityId' => $entityId,
            'addedBy' => $addedBy,
        ]);
    }

    public function getDependencies(): array
    {
        return [CountryFixtures::class, UserFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
