<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrganizationGraphDataEndpointTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            KernelTestCase::bootKernel();
            $em = static::getContainer()->get(EntityManagerInterface::class);
            self::assertInstanceOf(EntityManagerInterface::class, $em);
            $em->getConnection()->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de données indisponible pour ce test : '.$e->getMessage());
        } finally {
            KernelTestCase::ensureKernelShutdown();
        }
    }

    public function testGraphDataReturnsCentralOrgOnlyWhenNoMembers(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getEntityManager();
        $org = $this->persistApprovedOrg($em, $suffix.'empty');

        $client->request('GET', '/en/organizations/'.$org->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($data['analyzing']);
        self::assertSame(0, $data['connectionCount']);
        self::assertCount(1, $data['elements']['nodes']);
        self::assertSame([], $data['elements']['edges']);
        self::assertSame('org-'.$org->getId(), $data['elements']['nodes'][0]['data']['id']);
        self::assertSame('central', $data['elements']['nodes'][0]['classes'] ?? null);
    }

    public function testGraphDataReturnsMembersAndEdges(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getEntityManager();
        $org = $this->persistApprovedOrg($em, $suffix.'org');
        $person = $this->persistApprovedPerson($em, $suffix.'p');
        $this->persistMembership($em, $person, $org, 2024);

        $client->request('GET', '/en/organizations/'.$org->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($data['analyzing']);
        self::assertGreaterThanOrEqual(1, $data['connectionCount']);

        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('org-'.$org->getId(), $nodeIds);
        self::assertContains('person-'.$person->getId(), $nodeIds);

        $hasOrgToPerson = false;
        foreach ($data['elements']['edges'] as $edge) {
            $s = $edge['data']['source'] ?? '';
            $t = $edge['data']['target'] ?? '';
            if ($s === 'org-'.$org->getId() && $t === 'person-'.$person->getId()) {
                $hasOrgToPerson = true;
                break;
            }
        }
        self::assertTrue($hasOrgToPerson, 'arête organisation → membre attendue');
    }

    public function testGraphDataIncludesOtherOrganizationsLinkedToMembers(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getEntityManager();
        $centralOrg = $this->persistApprovedOrg($em, $suffix.'central');
        $otherOrg = $this->persistApprovedOrg($em, $suffix.'other');
        $person = $this->persistApprovedPerson($em, $suffix.'member');
        $this->persistMembership($em, $person, $centralOrg, 2024);
        $this->persistMembership($em, $person, $otherOrg, 2023);

        $client->request('GET', '/en/organizations/'.$centralOrg->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('org-'.$centralOrg->getId(), $nodeIds);
        self::assertContains('org-'.$otherOrg->getId(), $nodeIds);
        self::assertContains('person-'.$person->getId(), $nodeIds);

        $hasPersonToOtherOrg = false;
        foreach ($data['elements']['edges'] as $edge) {
            $s = $edge['data']['source'] ?? '';
            $t = $edge['data']['target'] ?? '';
            if ($s === 'person-'.$person->getId() && $t === 'org-'.$otherOrg->getId()) {
                $hasPersonToOtherOrg = true;
                break;
            }
        }
        self::assertTrue($hasPersonToOtherOrg, 'arête personne → organisation affiliée attendue');
    }

    public function testGraphDataIncludesCoMembersLinkedThroughAffiliatedOrganization(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getEntityManager();
        $centralOrg = $this->persistApprovedOrg($em, $suffix.'central');
        $otherOrg = $this->persistApprovedOrg($em, $suffix.'other');
        $bridge = $this->persistApprovedPerson($em, $suffix.'bridge');
        $onlyOther = $this->persistApprovedPerson($em, $suffix.'onlyother');
        $this->persistMembership($em, $bridge, $centralOrg, 2024);
        $this->persistMembership($em, $bridge, $otherOrg, 2023);
        $this->persistMembership($em, $onlyOther, $otherOrg, 2022);

        $client->request('GET', '/en/organizations/'.$centralOrg->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('person-'.$bridge->getId(), $nodeIds);
        self::assertContains('person-'.$onlyOther->getId(), $nodeIds);
        self::assertContains('org-'.$otherOrg->getId(), $nodeIds);

        $hasCoMemberToAffiliateOrg = false;
        foreach ($data['elements']['edges'] as $edge) {
            $s = $edge['data']['source'] ?? '';
            $t = $edge['data']['target'] ?? '';
            if ($s === 'person-'.$onlyOther->getId() && $t === 'org-'.$otherOrg->getId()) {
                $hasCoMemberToAffiliateOrg = true;
                break;
            }
        }
        self::assertTrue($hasCoMemberToAffiliateOrg, 'co-membre lié à une organisation affiliée attendu');
    }

    public function testGraphData404WhenOrganizationNotApproved(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getEntityManager();
        $org = $this->persistApprovedOrg($em, $suffix);
        $slug = $org->getSlug();
        $org->setStatus(Organization::STATUS_PENDING);
        $em->flush();

        $client->request('GET', '/en/organizations/'.$slug.'/graph-data');

        self::assertResponseStatusCodeSame(404);
    }

    private function persistApprovedOrg(EntityManagerInterface $em, string $suffix): Organization
    {
        $o = new Organization();
        $o->setOfficialName('Organisation test '.$suffix);
        $o->setType(Organization::TYPE_INFLUENCE_NETWORK);
        $o->setStatus(Organization::STATUS_APPROVED);
        $em->persist($o);
        $em->flush();

        return $o;
    }

    private function persistApprovedPerson(EntityManagerInterface $em, string $suffix): Person
    {
        $p = new Person();
        $p->setGivenName('Prénom');
        $p->setFamilyName('Nom'.$suffix);
        $p->setStatus(Person::STATUS_APPROVED);
        $p->setRoleCategories(['politician']);
        $em->persist($p);
        $em->flush();

        $slug = 'graph-org-member-'.$suffix;
        $em->createQueryBuilder()
            ->update(Person::class, 'person')
            ->set('person.slug', ':slug')
            ->where('person.id = :id')
            ->setParameter('slug', $slug)
            ->setParameter('id', $p->getId())
            ->getQuery()
            ->execute();
        $em->refresh($p);

        return $p;
    }

    private function persistMembership(EntityManagerInterface $em, Person $person, Organization $org, int $year): void
    {
        $m = new Membership();
        $m->setPerson($person);
        $m->setOrganization($org);
        $m->setYear($year);
        $m->setStatus('approved');
        $em->persist($m);
        $em->flush();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        return $em;
    }
}
