<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Source\Entity\Source;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Source>
 */
final class SourceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Source::class;
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        return [
            'url' => 'https://fixture.publicgraph.test/'.$faker->unique()->uuid(),
            'title' => $faker->sentence(3),
            'type' => Source::TYPE_OFFICIAL_PUBLICATION,
            'accessedAt' => new \DateTimeImmutable('today'),
            'checkStatus' => Source::CHECK_UNCHECKED,
            'lastCheckedAt' => null,
            'waybackUrl' => null,
        ];
    }
}
