<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class CronTelemetry
{
    private const FILE = 'cron-telemetry.json';

    public function __construct(
        #[Autowire('%kernel.project_dir%/var')]
        private readonly string $varDir,
    ) {
    }

    public function recordSuccess(string $commandName): void
    {
        $path = $this->varDir.'/'.self::FILE;
        $data = [];
        if (is_file($path)) {
            $raw = file_get_contents($path);
            if (\is_string($raw) && '' !== $raw) {
                try {
                    /** @var array<string, mixed> $decoded */
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    $data = $decoded;
                } catch (\JsonException) {
                    $data = [];
                }
            }
        }

        $data[$commandName] = [
            'lastSuccessAt' => (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
        ];

        if (!is_dir($this->varDir) && !mkdir($this->varDir, 0775, true) && !is_dir($this->varDir)) {
            return;
        }

        file_put_contents(
            $path,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return array<string, array{lastSuccessAt?: string}>
     */
    public function readAll(): array
    {
        $path = $this->varDir.'/'.self::FILE;
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (!\is_string($raw) || '' === $raw) {
            return [];
        }

        try {
            /* @var array<string, array{lastSuccessAt?: string}> */
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
    }
}
