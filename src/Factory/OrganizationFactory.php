<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Organization\Entity\Organization;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Organization>
 */
final class OrganizationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Organization::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'officialName' => $faker->unique()->company(),
            'type' => Organization::TYPE_OTHER,
            'status' => Organization::STATUS_APPROVED,
            'websiteUrl' => $faker->optional(0.7)->url(),
            'foundedYear' => $faker->optional()->numberBetween(1800, (int) date('Y')),
        ];
    }
}
