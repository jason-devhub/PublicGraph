<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Repository\OrganizationRepository;

final class OrganizationRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testFindByType(): void
    {
        $suffix = $this->newUserSuffix();
        $net = $this->persistOrganization($suffix.'net', Organization::TYPE_INFLUENCE_NETWORK, 'approved');
        $this->persistOrganization($suffix.'corp', Organization::TYPE_CORPORATION, 'approved');

        $repo = $this->getRepository();
        $networks = $repo->findByType(Organization::TYPE_INFLUENCE_NETWORK);

        self::assertContainsOrgId($networks, (int) $net->getId());
        foreach ($networks as $o) {
            self::assertSame(Organization::TYPE_INFLUENCE_NETWORK, $o->getType());
        }
    }

    public function testFindApproved(): void
    {
        $suffix = $this->newUserSuffix();
        $ok = $this->persistOrganization($suffix.'ok', Organization::TYPE_OTHER, 'approved');
        $this->persistOrganization($suffix.'pend', Organization::TYPE_OTHER, 'pending');

        $repo = $this->getRepository();
        $list = $repo->findApproved();

        self::assertContainsOrgId($list, (int) $ok->getId());
        foreach ($list as $o) {
            self::assertSame('approved', $o->getStatus());
        }
    }

    public function testFindInfluenceNetworks(): void
    {
        $suffix = $this->newUserSuffix();
        $inf = $this->persistOrganization($suffix.'inf', Organization::TYPE_INFLUENCE_NETWORK, 'approved');

        $repo = $this->getRepository();
        $list = $repo->findInfluenceNetworks();

        self::assertContainsOrgId($list, (int) $inf->getId());
    }

    private function getRepository(): OrganizationRepository
    {
        $repo = $this->getEntityManager()->getRepository(Organization::class);
        \assert($repo instanceof OrganizationRepository);

        return $repo;
    }

    /**
     * @param list<Organization> $list
     */
    private static function assertContainsOrgId(array $list, int $id): void
    {
        $ids = array_map(static fn (Organization $o) => $o->getId(), $list);
        self::assertContains($id, $ids);
    }
}
