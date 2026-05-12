<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MinSourcesValidatorFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testFailsWhenNoSources(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $membership = new Membership();
        $membership->setPerson($person);
        $membership->setOrganization($org);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $violations = $this->getValidator()->validate($membership);
        self::assertGreaterThan(0, $violations->count(), 'Sans source documentaire, la validation doit échouer.');
    }

    public function testPassesWithAtLeastOneSource(): void
    {
        $suffix = $this->newUserSuffix();
        $person = $this->persistPerson($suffix.'p', Person::STATUS_APPROVED);
        $org = $this->persistOrganization($suffix.'o', Organization::TYPE_OTHER, 'approved');

        $membership = new Membership();
        $membership->setPerson($person);
        $membership->setOrganization($org);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $source = new Source();
        $source->setUrl(\sprintf('https://example.org/minsrc-%s', $suffix));
        $source->setType(Source::TYPE_OTHER);
        $this->entityManager->persist($source);

        $link = new EntitySource();
        $link->setSource($source);
        $link->setEntityType(EntitySource::ENTITY_MEMBERSHIP);
        $link->setEntityId((int) $membership->getId());
        $this->entityManager->persist($link);
        $this->entityManager->flush();

        $violations = $this->getValidator()->validate($membership);
        self::assertCount(0, $violations);
    }

    private function getValidator(): ValidatorInterface
    {
        $v = self::getContainer()->get(ValidatorInterface::class);
        \assert($v instanceof ValidatorInterface);

        return $v;
    }
}
