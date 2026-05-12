<?php

declare(strict_types=1);

namespace App\Module\Catalog\Sitemap;

use App\Module\Legislation\Repository\RevolvingDoorRepository;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RevolvingDoorSitemapPopulateListener implements EventSubscriberInterface
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly RevolvingDoorRepository $revolvingDoorRepository,
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
        if (null !== $section && 'revolving-doors' !== $section) {
            return;
        }

        $router = $event->getUrlGenerator();
        foreach ($this->revolvingDoorRepository->iterateApprovedForSitemap() as $door) {
            $id = $door->getId();
            if (null === $id) {
                continue;
            }

            foreach ($this->enabledLocales as $locale) {
                $event->getUrlContainer()->addUrl(
                    new UrlConcrete(
                        $router->generate(
                            'app_revolving_door_show',
                            ['id' => $id, '_locale' => $locale],
                            UrlGeneratorInterface::ABSOLUTE_URL,
                        ),
                    ),
                    'revolving-doors',
                );
            }
        }
    }
}
