<?php

declare(strict_types=1);

namespace App\Tests\Functional\Catalog;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Tests\Support\CatalogPublicFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CatalogPagesTest extends WebTestCase
{
    public function testPersonListReturnsTwentyPerPageAndApprovedOnly(): void
    {
        $client = static::createClient();
        CatalogPublicFixture::seed($client->getContainer()->get(EntityManagerInterface::class));

        $client->request('GET', '/en/people');
        self::assertResponseIsSuccessful();

        $xpathCount = $client->getCrawler()->filterXPath('//div[contains(@class,"md:block")]//table/tbody/tr')->count();
        self::assertLessThanOrEqual(20, $xpathCount);
        self::assertStringNotContainsString('Pending Face', $client->getResponse()->getContent());
    }

    public function testPersonShowApprovedAndPendingNotFound(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        CatalogPublicFixture::seed($em);

        $approved = $em->getRepository(Person::class)->findOneBy(['givenName' => 'Jean', 'familyName' => 'Public']);
        self::assertInstanceOf(Person::class, $approved);

        $client->request('GET', '/en/people/'.$approved->getSlug());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Jean Public');

        $pending = $em->getRepository(Person::class)->findOneBy(['givenName' => 'Pending', 'familyName' => 'Face']);
        self::assertInstanceOf(Person::class, $pending);

        $client->request('GET', '/en/people/'.$pending->getSlug());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testOrganizationListAndShow(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        CatalogPublicFixture::seed($em);

        $org = $em->getRepository(Organization::class)->findOneBy(['officialName' => 'WEF Test']);
        self::assertInstanceOf(Organization::class, $org);

        $client->request('GET', '/en/organizations');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'WEF Test');

        $client->request('GET', '/en/organizations/'.$org->getSlug());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'WEF Test');
    }

    public function testPersonFiltersLiveComponentBuilds(): void
    {
        $client = static::createClient();
        CatalogPublicFixture::seed($client->getContainer()->get(EntityManagerInterface::class));
        $client->request('GET', '/en/people');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-catalog="person-filters"]');
        self::assertSelectorTextContains('body', 'Nom');
    }
}
