<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirige les anciennes URLs sans préfixe de locale vers /{locale}/… (301 en GET, 307 sinon).
 */
final class LegacyUrlRedirectSubscriber implements EventSubscriberInterface
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly string $defaultLocale,
        private readonly array $enabledLocales,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', 120]]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ($this->isExcludedPath($path)) {
            return;
        }

        $firstSegment = $this->firstPathSegment($path);
        if (\in_array($firstSegment, $this->enabledLocales, true)) {
            return;
        }

        $locale = $this->resolveTargetLocale($request);
        $targetPath = '/'.$locale.('/' === $path ? '/' : $path);
        $qs = $request->getQueryString();
        if (null !== $qs && '' !== $qs) {
            $targetPath .= '?'.$qs;
        }

        $status = $request->isMethodCacheable() ? 301 : 307;
        $event->setResponse(new RedirectResponse($request->getUriForPath($targetPath), $status));
    }

    private function isExcludedPath(string $path): bool
    {
        if ('' === $path || '/' === $path) {
            return false;
        }

        foreach ([
            '/admin',
            '/_profiler',
            '/_wdt',
            '/_components',
            '/assets/',
            '/build/',
            '/bundles/',
            '/health',
            '/sitemap',
            '/robots.txt',
            '/.well-known',
        ] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function firstPathSegment(string $path): string
    {
        $path = trim($path, '/');
        if ('' === $path) {
            return '';
        }

        $parts = explode('/', $path);

        return $parts[0] ?? '';
    }

    private function resolveTargetLocale(Request $request): string
    {
        $cookie = $request->cookies->get('preferred_locale');
        if (\is_string($cookie) && \in_array($cookie, $this->enabledLocales, true)) {
            return $cookie;
        }

        $preferred = $request->getPreferredLanguage($this->enabledLocales);
        if (\is_string($preferred) && \in_array($preferred, $this->enabledLocales, true)) {
            return $preferred;
        }

        return $this->defaultLocale;
    }
}
