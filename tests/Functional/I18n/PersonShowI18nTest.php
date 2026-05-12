<?php

declare(strict_types=1);

namespace App\Tests\Functional\I18n;

use App\Module\Person\Entity\Person;
use App\Tests\Support\CatalogPublicFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PersonShowI18nTest extends WebTestCase
{
    public function testPersonShowHasHreflangAndHtmlLang(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        CatalogPublicFixture::seed($em);

        $approved = $em->getRepository(Person::class)->findOneBy(['givenName' => 'Jean', 'familyName' => 'Public']);
        self::assertInstanceOf(Person::class, $approved);
        $slug = $approved->getSlug();
        self::assertNotNull($slug);

        $client->request('GET', '/fr/people/'.$slug);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="fr"]');
        self::assertSelectorExists('link[rel="alternate"][hreflang="en"]');
        self::assertSelectorExists('link[rel="alternate"][hreflang="fr"]');
        self::assertSelectorExists('link[rel="alternate"][hreflang="x-default"]');
    }
}
