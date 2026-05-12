<?php

declare(strict_types=1);

namespace App\Module\Legal\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CloudflareTurnstileVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $secretKey,
        private readonly string $environment,
    ) {
    }

    public function verify(?string $token, Request $request): bool
    {
        if (('' === (string) $this->secretKey) && \in_array($this->environment, ['dev', 'test'], true)) {
            return true;
        }

        if ('test' === $this->environment && 'test-turnstile-token' === $token) {
            return true;
        }

        if ('' === (string) $token || null === $this->secretKey || '' === $this->secretKey) {
            return false;
        }

        $response = $this->httpClient->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $request->getClientIp(),
            ],
        ]);

        /** @var array{success?: bool} $data */
        $data = $response->toArray(false);

        return true === ($data['success'] ?? false);
    }
}
