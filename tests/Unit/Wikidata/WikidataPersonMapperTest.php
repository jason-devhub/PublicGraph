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
}
