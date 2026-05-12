<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Influence\Entity\Position;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Position>
 */
final class PositionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Position::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'person' => PersonFactory::new(),
            'organization' => OrganizationFactory::new(),
            'titleFr' => $faker->jobTitle(),
            'titleEn' => $faker->optional()->jobTitle(),
            'nature' => $faker->randomElement([
                Position::NATURE_ELECTED_OFFICE,
                Position::NATURE_APPOINTED_OFFICE,
                Position::NATURE_CORPORATE_POSITION,
                Position::NATURE_BOARD_MEMBER,
                Position::NATURE_ADVISOR,
                Position::NATURE_OTHER,
            ]),
            'startDate' => \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 years', '-1 year')),
            'endDate' => null,
            'country' => null,
            'status' => 'approved',
            'createdBy' => null,
        ];
    }
}
