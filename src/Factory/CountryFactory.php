<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Catalog\Entity\Country;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Country>
 */
final class CountryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Country::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'isoCode' => $faker->unique()->regexify('[A-Z]{2}'),
            'nameFr' => $faker->country(),
            'nameEn' => $faker->country(),
            'continent' => $faker->randomElement(['Europe', 'Americas', 'Asia', 'Africa', 'Oceania', 'Unknown']),
        ];
    }
}
