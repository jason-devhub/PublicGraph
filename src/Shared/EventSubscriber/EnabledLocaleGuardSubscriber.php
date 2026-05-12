<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejette les codes de locale présents dans l’URL mais absents de APP_ENABLED_LOCALES.
 */
final class EnabledLocaleGuardSubscriber implements EventSubscriberInterface
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly array $enabledLocales,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', -20]]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->attributes->get('_locale');
        if (!\is_string($locale) || '' === $locale) {
            return;
        }

        if (!\in_array($locale, $this->enabledLocales, true)) {
            throw new NotFoundHttpException(sprintf('Locale "%s" is not enabled.', $locale));
        }
    }
}
