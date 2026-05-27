<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Graph\Model;

use App\Module\Graph\Model\GraphQueryParams;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class GraphQueryParamsTest extends TestCase
{
    public function testFromRequestAcceptsCountryAndCategoryArrays(): void
    {
        $request = Request::create('/en/graph', 'GET', [
            'countries' => ['FR', 'de'],
            'categories' => ['politician', 'lobbyist'],
        ]);
        $request->setLocale('en');

        $params = GraphQueryParams::fromRequest($request);

        self::assertSame(['FR', 'DE'], $params->countryIsoCodes);
        self::assertSame(['politician', 'lobbyist'], $params->roleCategories);
    }

    public function testFromRequestAcceptsCommaSeparatedLegacyStrings(): void
    {
        $request = Request::create('/en/graph', 'GET', [
            'countries' => 'fr, DE',
            'categories' => 'politician, civil_servant',
        ]);
        $request->setLocale('en');

        $params = GraphQueryParams::fromRequest($request);

        self::assertSame(['FR', 'DE'], $params->countryIsoCodes);
        self::assertSame(['politician', 'civil_servant'], $params->roleCategories);
    }
}
