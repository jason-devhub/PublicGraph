<?php

declare(strict_types=1);

namespace App\Module\Catalog\Sitemap;

use App\Module\Person\Repository\PersonRepository;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PersonSitemapPopulateListener implements EventSubscriberInterface
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly array $enabledLocales,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => 'populate',
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        $section = $event->getSection();
        if (null !== $section && 'persons' !== $section) {
            return;
        }

        $router = $event->getUrlGenerator();
        foreach ($this->personRepository->iterateApprovedForSitemap() as $person) {
            $slug = $person->getSlug();
            if ('' === $slug) {
                continue;
            }

            foreach ($this->enabledLocales as $locale) {
                $event->getUrlContainer()->addUrl(
                    new UrlConcrete(
                        $router->generate(
                            'app_person_show',
                            ['slug' => $slug, '_locale' => $locale],
                            UrlGeneratorInterface::ABSOLUTE_URL,
                        ),
                    ),
                    'persons',
                );
            }
        }
    }
}
