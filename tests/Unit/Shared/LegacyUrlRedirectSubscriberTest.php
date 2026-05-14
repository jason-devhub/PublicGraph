<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\EventSubscriber\LegacyUrlRedirectSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class LegacyUrlRedirectSubscriberTest extends TestCase
{
    public function testLiveComponentPathIsNotLocaleRedirected(): void
    {
        $subscriber = new LegacyUrlRedirectSubscriber('en', ['en', 'fr']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/_components/CatalogPersonFilters/setPage', 'POST');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        self::assertFalse($event->hasResponse(), 'UX Live Component requests must hit /_components without /{locale}/ prefix.');
    }

    public function testBarePathStillRedirectsToLocale(): void
    {
        $subscriber = new LegacyUrlRedirectSubscriber('en', ['en', 'fr']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/people', 'GET');
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(301, $event->getResponse()->getStatusCode());
        $location = $event->getResponse()->headers->get('Location');
        self::assertIsString($location);
        self::assertStringEndsWith('/en/people', $location);
    }
}
