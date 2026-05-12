<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Module\Person\Entity\Person;
use App\Shared\I18n\LocalizedContentResolver;
use App\Shared\Seo\CatalogSeoPresenter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

final class CatalogSeoPresenterTest extends TestCase
{
    public function testPersonJsonLdContainsExpectedSchemaKeys(): void
    {
        $person = new Person();
        $person->setGivenName('Jean');
        $person->setFamilyName('Dupont');
        $person->setStatus(Person::STATUS_APPROVED);
        $ref = new \ReflectionProperty(Person::class, 'slug');
        $ref->setValue($person, 'jean-dupont');

        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'seo.person.title' => '%name% — PN',
            'seo.person.og_title' => '%name% — OG',
            'seo.person.role_fallback' => 'fallback role',
            'seo.person.org_sample' => ' inc %orgs%',
            'seo.person.meta' => '%name%|%roles%|%membership_count%|%org_part%|%position_count%',
        ], 'en', 'messages');

        $presenter = new CatalogSeoPresenter($translator, new LocalizedContentResolver(['en', 'fr']));
        $head = $presenter->buildPersonHead($person, 'https://example.org/en/people/jean-dupont', null, 'en');

        self::assertArrayHasKey('jsonLd', $head);
        /** @var array<string, mixed> $data */
        $data = json_decode($head['jsonLd'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('https://schema.org', $data['@context']);
        self::assertSame('Person', $data['@type']);
        self::assertSame('Jean Dupont', $data['name']);
        self::assertSame('https://example.org/en/people/jean-dupont', $data['url']);
    }
}
