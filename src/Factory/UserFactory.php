<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\User\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'email' => $faker->unique()->safeEmail(),
            'username' => 'u_'.$faker->unique()->numerify('########'),
            'password' => password_hash('TestPassword123!', \PASSWORD_DEFAULT),
            'roles' => ['ROLE_USER'],
            'status' => 'active',
            'cguAcceptedAt' => new \DateTimeImmutable(),
            'emailVerifiedAt' => new \DateTimeImmutable(),
        ];
    }
}
