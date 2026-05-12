<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Influence\Entity\Membership;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Membership>
 */
final class MembershipFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Membership::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'person' => PersonFactory::new(),
            'organization' => OrganizationFactory::new(),
            'year' => $faker->numberBetween(2018, 2025),
            'startDate' => null,
            'endDate' => null,
            'roleInOrganization' => $faker->optional()->randomElement(['membre', 'intervenant', 'participant']),
            'status' => 'approved',
            'createdBy' => null,
        ];
    }
}
