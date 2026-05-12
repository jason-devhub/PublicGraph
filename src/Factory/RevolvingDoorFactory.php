<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Legislation\Entity\RevolvingDoor;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<RevolvingDoor>
 */
final class RevolvingDoorFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return RevolvingDoor::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'person' => PersonFactory::new(),
            'sourcePosition' => PositionFactory::new(),
            'targetPosition' => PositionFactory::new(),
            'linkingAction' => null,
            'factualNoteFr' => $faker->optional()->paragraph(),
            'factualNoteEn' => $faker->optional()->paragraph(),
            'status' => 'approved',
            'createdBy' => null,
        ];
    }
}
