<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Module\Catalog\Entity\Country;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Intl\Countries;

final class CountryFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $path = dirname(__DIR__, 2).'/data/iso3166-countries.json';
        $continents = [];
        if (is_readable($path)) {
            /** @var list<array{alpha-2: string, region?: string}> $rows */
            $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            foreach ($rows as $row) {
                $code = $row['alpha-2'] ?? '';
                if ('' !== $code) {
                    $continents[$code] = (string) ($row['region'] ?? 'Unknown');
                }
            }
        }

        foreach (Countries::getNames('fr') as $alpha2 => $nameFr) {
            $nameEn = Countries::getName($alpha2, 'en');
            $continent = $continents[$alpha2] ?? 'Unknown';
            $manager->persist(new Country($alpha2, $nameFr, $nameEn, $continent));
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
