<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GraphDataApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            KernelTestCase::bootKernel();
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->getConnection()->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de données indisponible pour ce test : '.$e->getMessage());
        } finally {
            KernelTestCase::ensureKernelShutdown();
        }
    }

    public function testGraphDataReturnsJsonElements(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/api/graph/data?maxNodes=10');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('elements', $data);
        self::assertArrayHasKey('nodes', $data['elements']);
        self::assertArrayHasKey('edges', $data['elements']);
    }

    public function testGlobalGraphIncludesOrganizationsAndPersonOrgEdges(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $cat = 'graph_api_global_'.$suffix;
        $person = $this->persistApprovedPersonWithCategory($em, $suffix, $cat);
        $org = $this->persistApprovedOrg($em, $suffix);
        $this->persistMembership($em, $person, $org, 2023);

        $client->request('GET', '/en/api/graph/data?maxNodes=50&categories='.rawurlencode($cat));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('person-'.$person->getId(), $nodeIds);
        self::assertContains('org-'.$org->getId(), $nodeIds);

        $orgNode = null;
        foreach ($data['elements']['nodes'] as $n) {
            if (($n['data']['id'] ?? '') === 'org-'.$org->getId()) {
                $orgNode = $n;
                break;
            }
        }
        self::assertIsArray($orgNode);
        self::assertSame('organization', $orgNode['data']['type'] ?? null);
        self::assertSame('#7B1A1A', $orgNode['data']['bgColor'] ?? null, 'couleur type influence_network alignée GraphDataBuilder');

        $hasPersonToOrg = false;
        foreach ($data['elements']['edges'] as $edge) {
            $s = $edge['data']['source'] ?? '';
            $t = $edge['data']['target'] ?? '';
            if ($s === 'person-'.$person->getId() && $t === 'org-'.$org->getId()) {
                $hasPersonToOrg = true;
                break;
            }
        }
        self::assertTrue($hasPersonToOrg, 'arête personne → organisation attendue');
    }

    private function persistApprovedPersonWithCategory(EntityManagerInterface $em, string $suffix, string $category): Person
    {
        $p = new Person();
        $p->setGivenName('Prénom');
        $p->setFamilyName('Nom'.$suffix);
        $p->setStatus(Person::STATUS_APPROVED);
        $p->setRoleCategories([$category]);
        $em->persist($p);
        $em->flush();

        $slug = 'graph-api-person-'.$suffix;
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
}
