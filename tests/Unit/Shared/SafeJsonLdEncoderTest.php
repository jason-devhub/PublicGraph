<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Seo\SafeJsonLdEncoder;
use PHPUnit\Framework\TestCase;

final class SafeJsonLdEncoderTest extends TestCase
{
    public function testEncodeArrayEscapesScriptBreakingSequences(): void
    {
        $json = SafeJsonLdEncoder::encodeArray([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'Evil</script><script>alert(1)</script>',
        ]);

        self::assertStringNotContainsString('</script>', $json);
        self::assertStringContainsString('\u003C', $json);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Evil</script><script>alert(1)</script>', $decoded['name']);
    }
}
