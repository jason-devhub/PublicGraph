<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Proximity\Entity\PersonSimilarity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PersonGraphDataEndpointTest extends WebTestCase
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

    public function testGraphDataReturnsGlobalGraphIncludingPerson(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $this->persistApprovedPerson($em, $suffix);

        $client->request('GET', '/en/people/'.$person->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($data['analyzing']);
        self::assertIsInt($data['connectionCount']);
        self::assertGreaterThanOrEqual(0, $data['connectionCount']);
        self::assertGreaterThanOrEqual(1, \count($data['elements']['nodes']));
        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('person-'.$person->getId(), $nodeIds);
    }

    public function testGraphDataPersonProfileIncludesOrganizationNodesForMemberships(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $this->persistApprovedPerson($em, $suffix.'mem');
        $org = $this->persistApprovedOrg($em, $suffix.'org');
        $this->persistMembership($em, $person, $org, 2023);

        $client->request('GET', '/en/people/'.$person->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($data['analyzing']);
        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('person-'.$person->getId(), $nodeIds);
        self::assertContains('org-'.$org->getId(), $nodeIds);

        $hasPersonToOrg = false;
        foreach ($data['elements']['edges'] as $edge) {
            $s = $edge['data']['source'] ?? '';
            $t = $edge['data']['target'] ?? '';
            if ($s === 'person-'.$person->getId() && $t === 'org-'.$org->getId()) {
                $hasPersonToOrg = true;
                break;
            }
        }
        self::assertTrue($hasPersonToOrg, 'arête personne → organisation attendue sur la fiche personne');
    }

    public function testGraphDataReturnsSimilarityEdgeWhenBothPersonsInGraph(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $central = $this->persistApprovedPerson($em, $suffix.'a');
        $other = $this->persistApprovedPerson($em, $suffix.'b');
        $org = $this->persistApprovedOrg($em, $suffix);

        $this->persistMembership($em, $central, $org, 2022);
        $this->persistSimilarity($em, $central, $other, '42.50');

        $client->request('GET', '/en/people/'.$central->getSlug().'/graph-data');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($data['analyzing']);

        $nodeIds = array_map(
            static fn (array $n): string => (string) $n['data']['id'],
            $data['elements']['nodes'],
        );
        self::assertContains('person-'.$central->getId(), $nodeIds);

        $hasOther = \in_array('person-'.$other->getId(), $nodeIds, true);
        if ($hasOther) {
            $hasPersonEdge = false;
            $a = min((int) $central->getId(), (int) $other->getId());
            $b = max((int) $central->getId(), (int) $other->getId());
            foreach ($data['elements']['edges'] as $edge) {
                $s = $edge['data']['source'];
                $t = $edge['data']['target'];
                if ($s === 'person-'.$a && $t === 'person-'.$b) {
                    $hasPersonEdge = true;
                    break;
                }
            }
            self::assertTrue($hasPersonEdge, 'arête de similarité attendue lorsque les deux personnes sont dans le sous-graphe');
        }

        self::assertContains('org-'.$org->getId(), $nodeIds);
    }

    public function testGraphData404WhenPersonNotApproved(): void
    {
        $client = static::createClient();
        $suffix = bin2hex(random_bytes(4));
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $this->persistApprovedPerson($em, $suffix);
        $slug = $person->getSlug();
        $person->setStatus(Person::STATUS_PENDING);
        $em->flush();

        $client->request('GET', '/en/people/'.$slug.'/graph-data');

        self::assertResponseStatusCodeSame(404);
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

        // Gedmo Sluggable ne renseigne pas toujours le slug dans l'environnement test.
        $slug = 'graph-person-'.$suffix;
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

    private function persistSimilarity(EntityManagerInterface $em, Person $a, Person $b, string $score): void
    {
        $s = new PersonSimilarity();
        $s->setPersonA($a);
        $s->setPersonB($b);
        $s->setScore($score);
        $s->setDetails([]);
        $em->persist($s);
        $em->flush();
    }
}
