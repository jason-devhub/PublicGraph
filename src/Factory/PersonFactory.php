<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Person\Entity\Person;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Person>
 */
final class PersonFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Person::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'givenName' => $faker->firstName(),
            'familyName' => $faker->lastName(),
            'usageName' => null,
            'birthDate' => null,
            'deathDate' => null,
            'gender' => $faker->optional()->randomElement(['male', 'female', 'other']),
            'roleCategories' => ['politician'],
            'photoUrl' => null,
            'wikidataId' => $faker->boolean(30) ? 'Q'.$faker->unique()->numerify('########') : null,
            'status' => Person::STATUS_APPROVED,
            'createdBy' => null,
        ];
    }
}
