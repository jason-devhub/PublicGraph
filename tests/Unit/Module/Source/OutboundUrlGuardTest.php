<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Source;

use App\Module\Source\Service\OutboundUrlGuard;
use PHPUnit\Framework\TestCase;

final class OutboundUrlGuardTest extends TestCase
{
    private OutboundUrlGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new OutboundUrlGuard();
    }

    public function testAllowsHttpsPublicHost(): void
    {
        self::assertTrue($this->guard->isSafeForServerHttpRequest('https://www.example.org/doc'));
    }

    public function testRejectsHttp(): void
    {
        self::assertFalse($this->guard->isSafeForServerHttpRequest('http://example.org/'));
    }

    public function testRejectsPrivateIpv4(): void
    {
        self::assertFalse($this->guard->isSafeForServerHttpRequest('https://192.168.1.1/'));
        self::assertFalse($this->guard->isSafeForServerHttpRequest('https://10.0.0.1/'));
        self::assertFalse($this->guard->isSafeForServerHttpRequest('https://169.254.169.254/latest/meta-data/'));
    }

    public function testRejectsLocalhost(): void
    {
        self::assertFalse($this->guard->isSafeForServerHttpRequest('https://localhost/evil'));
    }

    public function testRejectsUserInfo(): void
    {
        self::assertFalse($this->guard->isSafeForServerHttpRequest('https://user:pass@example.org/'));
    }

    public function testRejectsTooLongUrl(): void
    {
        $long = 'https://example.org/'.str_repeat('a', 3000);
        self::assertFalse($this->guard->isSafeForServerHttpRequest($long));
    }
}
