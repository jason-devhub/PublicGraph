<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Source\Entity\EntitySource;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<EntitySource>
 */
final class EntitySourceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return EntitySource::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'source' => SourceFactory::new(),
            'entityType' => EntitySource::ENTITY_MEMBERSHIP,
            'entityId' => 1,
            'addedBy' => null,
        ];
    }
}
