<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $plain = 'TestPassword123!';

        $admin = (new User())
            ->setEmail('admin@example.com')
            ->setUsername('admin')
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->setEmailVerifiedAt(new \DateTimeImmutable());
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $plain));
        $this->userRepository->save($admin, false);

        $mod = (new User())
            ->setEmail('mod@example.com')
            ->setUsername('moderator')
            ->setRoles(['ROLE_MODERATOR', 'ROLE_USER'])
            ->setEmailVerifiedAt(new \DateTimeImmutable());
        $mod->setPassword($this->passwordHasher->hashPassword($mod, $plain));
        $this->userRepository->save($mod, false);

        $user = (new User())
            ->setEmail('user@example.com')
            ->setUsername('user')
            ->setRoles(['ROLE_USER'])
            ->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
        $this->userRepository->save($user, false);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
