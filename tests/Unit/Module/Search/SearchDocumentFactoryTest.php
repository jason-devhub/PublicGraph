<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Search;

use App\Module\Catalog\Entity\Country;
use App\Module\Person\Entity\Person;
use App\Module\Search\Service\SearchDocumentFactory;
use PHPUnit\Framework\TestCase;

final class SearchDocumentFactoryTest extends TestCase
{
    public function testPersonDocumentContainsExpectedKeys(): void
    {
        $person = new Person();
        $person->setGivenName('Jean');
        $person->setFamilyName('Dupont');
        $person->setUsageName('J. Dupont');
        $person->setStatus(Person::STATUS_APPROVED);
        $person->setRoleCategories(['politician']);
        $fr = new Country('FR', 'France', 'France', 'EU');
        $person->addNationality($fr);

        $ref = new \ReflectionProperty(Person::class, 'id');
        $ref->setValue($person, 1);

        $refSlug = new \ReflectionProperty(Person::class, 'slug');
        $refSlug->setValue($person, 'jean-dupont');

        $factory = new SearchDocumentFactory(['en', 'fr']);
        $doc = $factory->buildPersonDocument($person);

        self::assertSame('1', $doc['id']);
        self::assertSame('jean-dupont', $doc['slug']);
        self::assertSame('Jean Dupont', $doc['fullName']);
        self::assertSame('J. Dupont', $doc['usageName']);
        self::assertContains('FR', $doc['nationalities']);
        self::assertSame(['politician'], $doc['role_categories']);
        self::assertSame([], $doc['organizations']);
        self::assertArrayHasKey('description_en', $doc);
        self::assertArrayHasKey('description_fr', $doc);
        self::assertSame('', $doc['description_en']);
        self::assertSame('', $doc['description_fr']);
    }
}
