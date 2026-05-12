<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testSaveAndLoadUserByIdentifier(): void
    {
        $suffix = $this->newUserSuffix();
        $user = new User();
        $user->setEmail(\sprintf('load-%s@test.local', $suffix));
        $user->setUsername(\sprintf('load_%s', $suffix));
        $user->setPassword('x');

        $repo = $this->getRepository();
        $repo->save($user);

        $loaded = $repo->loadUserByIdentifier($user->getEmail());
        self::assertSame($user->getId(), $loaded->getId());
    }

    public function testRefreshUser(): void
    {
        $suffix = $this->newUserSuffix();
        $user = $this->persistUser($suffix);

        $repo = $this->getRepository();
        $refreshed = $repo->refreshUser($user);

        self::assertSame($user->getId(), $refreshed->getId());
        self::assertInstanceOf(User::class, $refreshed);
    }

    public function testSupportsClass(): void
    {
        $repo = $this->getRepository();
        self::assertTrue($repo->supportsClass(User::class));
        self::assertFalse($repo->supportsClass(\stdClass::class));
    }

    public function testUpgradePassword(): void
    {
        $suffix = $this->newUserSuffix();
        $user = $this->persistUser($suffix);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $repo = $this->getRepository();
        $newHash = $hasher->hashPassword($user, 'nouveau-mot-de-passe');
        $repo->upgradePassword($user, $newHash);

        $this->entityManager->clear();
        $reloaded = $repo->find($user->getId());
        \assert($reloaded instanceof User);
        self::assertTrue($hasher->isPasswordValid($reloaded, 'nouveau-mot-de-passe'));
    }

    private function getRepository(): UserRepository
    {
        $repo = $this->entityManager->getRepository(User::class);
        \assert($repo instanceof UserRepository);

        return $repo;
    }
}
