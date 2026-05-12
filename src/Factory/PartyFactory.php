<?php

declare(strict_types=1);

namespace App\Factory;

use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\Party;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Party>
 */
final class PartyFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Party::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'organization' => OrganizationFactory::new([
                'type' => Organization::TYPE_POLITICAL_PARTY,
                'officialName' => 'Parti '.self::faker()->unique()->words(3, true),
            ]),
            'europeanFamily' => null,
            'internationalFamily' => null,
            'colorHex' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Party $party): void {
            $organization = $party->getOrganization();
            if (null !== $organization && null === $organization->getPartyDetails()) {
                $organization->setPartyDetails($party);
            }
        });
    }
}
