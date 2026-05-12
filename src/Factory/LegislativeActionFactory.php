<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Legislation\Entity\LegislativeAction;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<LegislativeAction>
 */
final class LegislativeActionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return LegislativeAction::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'author' => PersonFactory::new(),
            'contextualPosition' => null,
            'type' => $faker->randomElement(['law_authored', 'vote', 'decree_signed', 'amendment', 'policy_decision']),
            'actionDate' => \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-5 years', 'now')),
            'titleFr' => $faker->sentence(4),
            'titleEn' => $faker->optional()->sentence(4),
            'descriptionFr' => $faker->paragraph(),
            'descriptionEn' => $faker->optional()->paragraph(),
            'status' => 'approved',
            'createdBy' => null,
        ];
    }
}
