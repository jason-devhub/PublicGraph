<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Source;

use App\Module\Source\Command\CheckSourceUrlsCommand;
use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\SourceRepository;
use App\Module\Source\Service\OutboundUrlGuard;
use App\Shared\Service\CronTelemetry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CheckSourceUrlsCommandTest extends TestCase
{
    public function testMarksSourceLiveOn200Head(): void
    {
        $source = new Source();
        $source->setUrl('https://example.com/page');
        $source->setType(Source::TYPE_PRESS_ARTICLE);

        $repo = $this->createMock(SourceRepository::class);
        $repo->method('findBatchForUrlCheck')->willReturn([$source]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::atLeastOnce())->method('flush');

        $base = sys_get_temp_dir().'/pn-test-'.uniqid('', true);
        mkdir($base, 0777, true);
        $telemetry = new CronTelemetry($base);

        $http = new MockHttpClient([
            new MockResponse('', ['http_code' => 200]),
        ]);

        $guard = new OutboundUrlGuard();

        $cmd = new CheckSourceUrlsCommand($repo, $em, $http, $telemetry, $guard);
        $tester = new CommandTester($cmd);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame(Source::CHECK_LIVE, $source->getCheckStatus());
    }
}
