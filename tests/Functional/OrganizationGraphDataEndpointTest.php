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
        self::assertSame(1, $data['connectionCount']);

        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('org-'.$org->getId(), $nodeIds);
        self::assertContains('person-'.$person->getId(), $nodeIds);

        $edge = $data['elements']['edges'][0];
        self::assertSame('org-'.$org->getId(), $edge['data']['source']);
        self::assertSame('person-'.$person->getId(), $edge['data']['target']);
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
