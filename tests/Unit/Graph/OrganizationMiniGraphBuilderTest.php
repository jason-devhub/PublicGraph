<?php

declare(strict_types=1);

namespace App\Tests\Unit\Graph;

use App\Module\Graph\Service\OrganizationMiniGraphBuilder;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Shared\I18n\LocalizedContentResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class OrganizationMiniGraphBuilderTest extends TestCase
{
    public function testBuildWithNoMembersReturnsCentralOrgOnly(): void
    {
        $org = $this->organizationWithId(3, 'Org vide', 'org-vide', Organization::TYPE_OTHER);

        $personRepo = $this->createMock(PersonRepository::class);
        $personRepo->expects(self::once())
            ->method('findApprovedMembersForOrganization')
            ->with($org, null, 80, 0)
            ->willReturn([]);

        $builder = new OrganizationMiniGraphBuilder(
            $personRepo,
            new LocalizedContentResolver(['en', 'fr']),
            $this->requestStackWithLocale('fr'),
        );
        $out = $builder->build($org);

        self::assertFalse($out['analyzing']);
        self::assertSame(0, $out['connectionCount']);
        self::assertCount(1, $out['elements']['nodes']);
        self::assertSame('org-3', $out['elements']['nodes'][0]['data']['id']);
        self::assertSame('central', $out['elements']['nodes'][0]['classes'] ?? null);
        self::assertSame([], $out['elements']['edges']);
    }

    public function testBuildLinksApprovedMembersToOrganization(): void
    {
        $org = $this->organizationWithId(5, 'Parti', 'parti-x', Organization::TYPE_POLITICAL_PARTY);
        $p1 = $this->personWithId(10, 'A', 'Un', 'a-un', ['politician']);
        $p2 = $this->personWithId(11, 'B', 'Deux', 'b-deux', ['lobbyist']);

        $personRepo = $this->createMock(PersonRepository::class);
        $personRepo->expects(self::once())
            ->method('findApprovedMembersForOrganization')
            ->with($org, null, 80, 0)
            ->willReturn([$p1, $p2]);

        $builder = new OrganizationMiniGraphBuilder(
            $personRepo,
            new LocalizedContentResolver(['en', 'fr']),
            $this->requestStackWithLocale('en'),
        );
        $out = $builder->build($org);

        self::assertFalse($out['analyzing']);
        self::assertSame(2, $out['connectionCount']);
        $ids = array_map(static fn (array $n): string => $n['data']['id'], $out['elements']['nodes']);
        self::assertContains('org-5', $ids);
        self::assertContains('person-10', $ids);
        self::assertContains('person-11', $ids);

        $sources = array_map(static fn (array $e): string => $e['data']['source'], $out['elements']['edges']);
        self::assertContains('org-5', $sources);
        self::assertCount(2, $out['elements']['edges']);
    }

    public function testBuildSkipsNonApprovedPersonsFromList(): void
    {
        $org = $this->organizationWithId(7, 'Org', 'org-y', Organization::TYPE_THINK_TANK);
        $pending = $this->personWithId(20, 'X', 'Pending', 'x-p', ['politician']);
        $pending->setStatus(Person::STATUS_PENDING);

        $personRepo = $this->createMock(PersonRepository::class);
        $personRepo->method('findApprovedMembersForOrganization')->willReturn([$pending]);

        $builder = new OrganizationMiniGraphBuilder(
            $personRepo,
            new LocalizedContentResolver(['en', 'fr']),
            $this->requestStackWithLocale('en'),
        );
        $out = $builder->build($org);

        self::assertSame(0, $out['connectionCount']);
        self::assertCount(1, $out['elements']['nodes']);
    }

    /**
     * @param list<string> $cats
     */
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
        $stack->push(Request::create('/'.$locale.'/organizations/test', 'GET'));

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
