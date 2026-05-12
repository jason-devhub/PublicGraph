<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enregistre la locale de la route courante pour les prochaines redirections depuis une URL sans préfixe.
 */
final class PreferredLocaleCookieSubscriber implements EventSubscriberInterface
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
        return [KernelEvents::RESPONSE => [['onKernelResponse', -10]]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->attributes->get('_locale');
        if (!\is_string($locale) || !\in_array($locale, $this->enabledLocales, true)) {
            return;
        }

        $existing = $request->cookies->get('preferred_locale');
        if ($existing === $locale) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->setCookie(new Cookie(
            'preferred_locale',
            $locale,
            new \DateTimeImmutable('+1 year'),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));
    }
}
