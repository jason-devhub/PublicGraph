<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wikidata;

use App\Module\Wikidata\Service\WikidataMembershipYearConsolidator;
use PHPUnit\Framework\TestCase;

final class WikidataMembershipYearConsolidatorTest extends TestCase
{
    public function testMergeAdjacentOrOverlappingYearIntervalsEmpty(): void
    {
        self::assertSame([], WikidataMembershipYearConsolidator::mergeAdjacentOrOverlappingYearIntervals([]));
    }

    public function testMergeAdjacentOrOverlappingYearIntervalsSingle(): void
    {
        $in = [['lo' => 2020, 'hi' => 2020]];
        self::assertSame($in, WikidataMembershipYearConsolidator::mergeAdjacentOrOverlappingYearIntervals($in));
    }

    public function testMergeAdjacentYears(): void
    {
        $in = [['lo' => 2018, 'hi' => 2018], ['lo' => 2019, 'hi' => 2019], ['lo' => 2020, 'hi' => 2020]];
        self::assertSame([['lo' => 2018, 'hi' => 2020]], WikidataMembershipYearConsolidator::mergeAdjacentOrOverlappingYearIntervals($in));
    }

    public function testMergeOverlapping(): void
    {
        $in = [['lo' => 2015, 'hi' => 2018], ['lo' => 2017, 'hi' => 2020]];
        self::assertSame([['lo' => 2015, 'hi' => 2020]], WikidataMembershipYearConsolidator::mergeAdjacentOrOverlappingYearIntervals($in));
    }

    public function testGapPreservesTwoGroups(): void
    {
        $in = [['lo' => 2015, 'hi' => 2016], ['lo' => 2018, 'hi' => 2019]];
        self::assertSame($in, WikidataMembershipYearConsolidator::mergeAdjacentOrOverlappingYearIntervals($in));
    }

    public function testUnsortedInput(): void
    {
        $in = [['lo' => 2022, 'hi' => 2022], ['lo' => 2020, 'hi' => 2020], ['lo' => 2021, 'hi' => 2021]];
        self::assertSame([['lo' => 2020, 'hi' => 2022]], WikidataMembershipYearConsolidator::mergeAdjacentOrOverlappingYearIntervals($in));
    }
}
