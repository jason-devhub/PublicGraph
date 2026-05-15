<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wikidata;

use App\Module\Wikidata\Service\WikidataPersonMapper;
use PHPUnit\Framework\TestCase;

final class WikidataPersonMapperTest extends TestCase
{
    public function testMapMinimalBinding(): void
    {
        $mapper = new WikidataPersonMapper();
        $binding = [
            'wikidataId' => ['type' => 'literal', 'value' => 'Q42'],
            'personLabel' => ['type' => 'literal', 'value' => 'Jean Dupont'],
            'nationalityQids' => ['type' => 'literal', 'value' => 'Q142|Q30'],
            'occupationQids' => ['type' => 'literal', 'value' => 'Q82955'],
        ];
        $dto = $mapper->map($binding);
        self::assertSame('Q42', $dto->wikidataId);
        self::assertSame('Jean', $dto->givenName);
        self::assertSame('Dupont', $dto->familyName);
        self::assertContains('politician', $dto->roleCategories);
        self::assertContains('Q142', $dto->nationalityQids);
    }

    public function testMapMinisterOccupation(): void
    {
        $mapper = new WikidataPersonMapper();
        $binding = [
            'wikidataId' => ['type' => 'literal', 'value' => 'Q1'],
            'personLabel' => ['type' => 'literal', 'value' => 'Marie Ministre'],
            'nationalityQids' => ['type' => 'literal', 'value' => 'Q142'],
            'occupationQids' => ['type' => 'literal', 'value' => 'Q83307'],
        ];
        $dto = $mapper->map($binding);
        self::assertContains('politician', $dto->roleCategories);
    }

    public function testMapPresidentOccupation(): void
    {
        $mapper = new WikidataPersonMapper();
        $binding = [
            'wikidataId' => ['type' => 'literal', 'value' => 'Q2'],
            'personLabel' => ['type' => 'literal', 'value' => 'Pat Président'],
            'nationalityQids' => ['type' => 'literal', 'value' => 'Q142'],
            'occupationQids' => ['type' => 'literal', 'value' => 'Q30461'],
        ];
        $dto = $mapper->map($binding);
        self::assertContains('politician', $dto->roleCategories);
    }

    public function testMapDeduplicatesBilingualPositionLabels(): void
    {
        $mapper = new WikidataPersonMapper();
        $binding = [
            'wikidataId' => ['type' => 'literal', 'value' => 'Q99'],
            'personLabel' => ['type' => 'literal', 'value' => 'Test Dupont'],
            'nationalityQids' => ['type' => 'literal', 'value' => 'Q142'],
            'occupationQids' => ['type' => 'literal', 'value' => 'Q82955'],
            'positionPairs' => ['type' => 'literal', 'value' => 'Q30185:mayor:1956-01-01:;;Q30185:maire:1956-01-01:;;Q193582:député français:1951-06-01:;;Q193582:member of the French National Assembly:1951-06-01:'],
        ];
        $dto = $mapper->map($binding);
        self::assertCount(2, $dto->positionsHeld);
        $byQ = [];
        foreach ($dto->positionsHeld as $p) {
            $byQ[$p['qid']] = $p['label'];
        }
        self::assertSame('mayor', $byQ['Q30185']);
        self::assertSame('member of the French National Assembly', $byQ['Q193582']);
    }

    public function testMapIncludesPhotoUrlFromP18(): void
    {
        $mapper = new WikidataPersonMapper();
        $binding = [
            'wikidataId' => ['type' => 'literal', 'value' => 'Q42'],
            'personLabel' => ['type' => 'literal', 'value' => 'Douglas Adams'],
            'nationalityQids' => ['type' => 'literal', 'value' => 'Q145'],
            'occupationQids' => ['type' => 'literal', 'value' => 'Q82955'],
            'image' => ['type' => 'literal', 'value' => 'Douglas Adams cropped.jpg'],
        ];
        $dto = $mapper->map($binding);
        self::assertStringContainsString('commons.wikimedia.org', (string) $dto->photoUrl);
        self::assertStringContainsString('Douglas_Adams_cropped.jpg', (string) $dto->photoUrl);
    }
}
