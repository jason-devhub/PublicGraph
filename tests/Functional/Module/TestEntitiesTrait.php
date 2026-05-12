<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;

trait TestEntitiesTrait
{
    protected function newUserSuffix(): string
    {
        return bin2hex(random_bytes(6));
    }

    protected function persistUser(string $suffix): User
    {
        $user = new User();
        $user->setEmail(\sprintf('user-%s@test.local', $suffix));
        $user->setUsername(\sprintf('u_%s', $suffix));
        $user->setPassword('x');
        $user->setRoles([]);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    protected function persistPerson(string $suffix, string $status = Person::STATUS_APPROVED, ?User $createdBy = null): Person
    {
        $person = new Person();
        $person->setGivenName('Prénom');
        $person->setFamilyName(\sprintf('Nom%s', $suffix));
        $person->setStatus($status);
        $person->setRoleCategories(['politician']);
        if (null !== $createdBy) {
            $person->setCreatedBy($createdBy);
        }
        $this->getEntityManager()->persist($person);
        $this->getEntityManager()->flush();

        return $person;
    }

    protected function persistOrganization(string $suffix, string $type = Organization::TYPE_OTHER, string $status = 'approved'): Organization
    {
        $org = new Organization();
        $org->setOfficialName(\sprintf('Org officielle %s', $suffix));
        $org->setType($type);
        $org->setStatus($status);
        $this->getEntityManager()->persist($org);
        $this->getEntityManager()->flush();

        return $org;
    }
}
