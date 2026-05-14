<?php

declare(strict_types=1);

namespace App\Tests\Unit\Graph;

use App\Module\Graph\Service\PersonMiniGraphBuilder;
use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Proximity\Entity\PersonSimilarity;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use App\Shared\I18n\LocalizedContentResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class PersonMiniGraphBuilderTest extends TestCase
{
    public function testAnalyzingPayloadWhenNoSimilarities(): void
    {
        $repo = $this->createMock(PersonSimilarityRepository::class);
        $repo->expects(self::once())->method('findTopForPerson')->willReturn([]);

        $person = $this->personWithId(101, 'Jean', 'Dupont', 'jean-dupont', ['politician']);

        $builder = new PersonMiniGraphBuilder($repo, new LocalizedContentResolver(['en', 'fr']), $this->requestStackWithLocale('en'));
        $out = $builder->build($person);

        self::assertTrue($out['analyzing']);
        self::assertSame(0, $out['connectionCount']);
        self::assertCount(1, $out['elements']['nodes']);
        self::assertSame('person-101', $out['elements']['nodes'][0]['data']['id']);
        self::assertSame('central', $out['elements']['nodes'][0]['classes'] ?? null);
        self::assertSame([], $out['elements']['edges']);
    }

    public function testNotAnalyzingWhenApprovedMembershipsExistWithoutSimilarities(): void
    {
        $repo = $this->createMock(PersonSimilarityRepository::class);
        $repo->expects(self::once())->method('findTopForPerson')->willReturn([]);

        $central = $this->personWithId(7, 'Solo', 'Membre', 'solo-membre', ['politician']);
        $org = $this->organizationWithId(99, 'Org seule', 'org-seule', Organization::TYPE_CORPORATION);
        $m = new Membership();
        $m->setPerson($central);
        $m->setOrganization($org);
        $m->setYear(2020);
        $m->setStatus('approved');
        $central->getMemberships()->add($m);

        $builder = new PersonMiniGraphBuilder($repo, new LocalizedContentResolver(['en', 'fr']), $this->requestStackWithLocale('en'));
        $out = $builder->build($central);

        self::assertFalse($out['analyzing']);
        self::assertSame(1, $out['connectionCount']);
        $ids = array_map(static fn (array $n): string => $n['data']['id'], $out['elements']['nodes']);
        self::assertContains('person-7', $ids);
        self::assertContains('org-99', $ids);
        self::assertCount(1, $out['elements']['edges']);
    }

    public function testBuildIncludesSimilarPersonsAndMembershipOrgs(): void
    {
        $central = $this->personWithId(1, 'A', 'Centrale', 'a-centrale', ['politician']);
        $other = $this->personWithId(2, 'B', 'Voisine', 'b-voisine', ['lobbyist']);

        $sim = new PersonSimilarity();
        $sim->setPersonA($central);
        $sim->setPersonB($other);
        $sim->setScore('88.00');
        $sim->setDetails([]);

        $repo = $this->createMock(PersonSimilarityRepository::class);
        $repo->method('findTopForPerson')->willReturn([$sim]);

        $org = $this->organizationWithId(50, 'Org Test', 'org-test', Organization::TYPE_THINK_TANK);
        $m = new Membership();
        $m->setPerson($central);
        $m->setOrganization($org);
        $m->setYear(2021);
        $m->setStatus('approved');
        $central->getMemberships()->add($m);

        $builder = new PersonMiniGraphBuilder($repo, new LocalizedContentResolver(['en', 'fr']), $this->requestStackWithLocale('en'));
        $out = $builder->build($central);

        self::assertFalse($out['analyzing']);
        self::assertGreaterThanOrEqual(2, $out['connectionCount']);
        $ids = array_map(static fn (array $n): string => $n['data']['id'], $out['elements']['nodes']);
        self::assertContains('person-1', $ids);
        self::assertContains('person-2', $ids);
        self::assertContains('org-50', $ids);
    }

    private function personWithId(int $id, string $given, string $family, string $slug, array $cats): Person
    {
        $p = new Person();
        $this->setPrivateId($p, $id);
        $p->setGivenName($given);
        $p->setFamilyName($family);
        $this->setPrivateProperty($p, 'slug', $slug);
        $p->setRoleCategories($cats);
        $p->setStatus(Person::STATUS_APPROVED);

        return $p;
    }

    private function organizationWithId(int $id, string $name, string $slug, string $type): Organization
    {
        $o = new Organization();
        $this->setPrivateId($o, $id);
        $o->setOfficialName($name);
        $this->setPrivateProperty($o, 'slug', $slug);
        $o->setType($type);
        $o->setStatus(Organization::STATUS_APPROVED);

        return $o;
    }

    private function requestStackWithLocale(string $locale): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'.$locale.'/people/test', 'GET'));

        return $stack;
    }

    private function setPrivateId(object $entity, int $id): void
    {
        (new \ReflectionProperty($entity, 'id'))->setValue($entity, $id);
    }

    private function setPrivateProperty(object $entity, string $prop, string $value): void
    {
        (new \ReflectionProperty($entity, $prop))->setValue($entity, $value);
    }
}
