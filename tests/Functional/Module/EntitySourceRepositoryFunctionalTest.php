<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\EntitySourceRepository;

final class EntitySourceRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testCountFor(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $membership = new Membership();
        $membership->setPerson($person);
        $membership->setOrganization($org);
        $membership->setYear(2024);
        $this->getEntityManager()->persist($membership);
        $this->getEntityManager()->flush();

        $source = $this->persistSource($suffix);
        $this->persistEntitySource($source, EntitySource::ENTITY_MEMBERSHIP, (int) $membership->getId());
        $this->persistEntitySource($source, EntitySource::ENTITY_MEMBERSHIP, (int) $membership->getId());

        $repo = $this->getRepository();
        self::assertSame(2, $repo->countFor(EntitySource::ENTITY_MEMBERSHIP, (int) $membership->getId()));
        self::assertSame(0, $repo->countFor(EntitySource::ENTITY_POSITION, 999999999));
    }

    private function persistSource(string $suffix): Source
    {
        $s = new Source();
        $s->setUrl(\sprintf('https://example.org/source-%s', $suffix));
        $s->setType(Source::TYPE_OTHER);
        $this->getEntityManager()->persist($s);
        $this->getEntityManager()->flush();

        return $s;
    }

    private function persistEntitySource(Source $source, string $entityType, int $entityId): EntitySource
    {
        $es = new EntitySource();
        $es->setSource($source);
        $es->setEntityType($entityType);
        $es->setEntityId($entityId);
        $this->getEntityManager()->persist($es);
        $this->getEntityManager()->flush();

        return $es;
    }

    private function getRepository(): EntitySourceRepository
    {
        $repo = $this->getEntityManager()->getRepository(EntitySource::class);
        \assert($repo instanceof EntitySourceRepository);

        return $repo;
    }
}
