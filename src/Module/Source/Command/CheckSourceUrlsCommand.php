<?php

declare(strict_types=1);

namespace App\Module\Source\Command;

use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\SourceRepository;
use App\Module\Source\Service\OutboundUrlGuard;
use App\Shared\Service\CronTelemetry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:sources:check-urls',
    description: 'Vérifie les URLs des sources (HEAD, statut live/mort/archivé Wayback).',
)]
final class CheckSourceUrlsCommand extends Command
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly CronTelemetry $cronTelemetry,
        private readonly OutboundUrlGuard $outboundUrlGuard,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sources = $this->sourceRepository->findBatchForUrlCheck(200);
        $now = new \DateTimeImmutable('now');

        foreach ($sources as $source) {
            $this->checkOne($source, $now);
            $this->entityManager->flush();
        }

        $this->cronTelemetry->recordSuccess('app:sources:check-urls');

        return Command::SUCCESS;
    }

    private function checkOne(Source $source, \DateTimeImmutable $now): void
    {
        $url = $source->getUrl();
        $source->setLastCheckedAt($now);

        if (!$this->outboundUrlGuard->isSafeForServerHttpRequest($url)) {
            $source->setCheckStatus(Source::CHECK_UNCHECKED);

            return;
        }

        try {
            $response = $this->httpClient->request('HEAD', $url, [
                'timeout' => 10,
                'max_redirects' => 3,
                'headers' => [
                    'User-Agent' => 'PublicGraphSourceBot/1.0 (+https://publicgraph.org)',
                ],
            ]);
            $status = $response->getStatusCode();
            $finalUrl = $response->getInfo('url');
            if (\is_string($finalUrl) && str_contains($finalUrl, 'web.archive.org')) {
                $source->setCheckStatus(Source::CHECK_ARCHIVED);
                $source->setWaybackUrl($finalUrl);

                return;
            }
        } catch (\Throwable) {
            $this->markDeadOrArchived($source, $url);

            return;
        }

        if ($status >= 200 && $status < 300) {
            $source->setCheckStatus(Source::CHECK_LIVE);
            $source->setWaybackUrl(null);

            return;
        }

        if (404 === $status || 410 === $status) {
            $this->markDeadOrArchived($source, $url);

            return;
        }

        $source->setCheckStatus(Source::CHECK_UNCHECKED);
    }

    private function markDeadOrArchived(Source $source, string $url): void
    {
        $wayback = $this->fetchWaybackUrl($url);
        if (null !== $wayback) {
            $source->setCheckStatus(Source::CHECK_ARCHIVED);
            $source->setWaybackUrl($wayback);
        } else {
            $source->setCheckStatus(Source::CHECK_DEAD);
            $source->setWaybackUrl(null);
        }
    }

    private function fetchWaybackUrl(string $url): ?string
    {
        $api = 'https://web.archive.org/wayback/available?url='.rawurlencode($url);
        try {
            $json = $this->httpClient->request('GET', $api, ['timeout' => 10])->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $closest = $json['archived_snapshots']['closest']['url'] ?? null;

        return \is_string($closest) && '' !== $closest ? $closest : null;
    }
}
