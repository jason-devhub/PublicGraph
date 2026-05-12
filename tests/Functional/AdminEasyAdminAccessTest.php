<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminEasyAdminAccessTest extends WebTestCase
{
    public function testAdminRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects();
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/login', $location);
    }

    public function testModeratorCanOpenAdminDashboard(): void
    {
        $client = static::createClient();

        $user = $this->createPersistedUser($client, 'moderator-ea@example.test', 'mod_ea', ['ROLE_MODERATOR']);
        $client->loginUser($user);

        $client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testPlainUserCannotAccessAdmin(): void
    {
        $client = static::createClient();

        $user = $this->createPersistedUser($client, 'user-ea@example.test', 'user_ea', ['ROLE_USER']);
        $client->loginUser($user);

        $client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @param list<string> $roles
     */
    private function createPersistedUser(
        \Symfony\Bundle\FrameworkBundle\KernelBrowser $client,
        string $email,
        string $username,
        array $roles,
    ): User {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $hasher = $client->getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($hasher->hashPassword($user, 'TestPassword123!'));
        $user->setRoles($roles);
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
