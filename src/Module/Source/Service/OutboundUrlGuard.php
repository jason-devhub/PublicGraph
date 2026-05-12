<?php

declare(strict_types=1);

namespace App\Module\Source\Service;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Réduit le risque SSRF pour les requêtes HTTP sortantes (ex. vérification HEAD des sources).
 */
final class OutboundUrlGuard
{
    private const MAX_URL_LENGTH = 2048;

    /**
     * Plages non routables / locales / metadata (RFC1918, loopback, link-local, CGNAT, ULA…).
     *
     * @var list<string>
     */
    private const BLOCKED_CIDRS = [
        '0.0.0.0/8',
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16',
        '100.64.0.0/10',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    public function isSafeForServerHttpRequest(string $url): bool
    {
        if (\strlen($url) > self::MAX_URL_LENGTH) {
            return false;
        }

        $parts = parse_url($url);
        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if ('' !== ($parts['user'] ?? '') || '' !== ($parts['pass'] ?? '')) {
            return false;
        }

        if (0 !== strcasecmp('https', (string) $parts['scheme'])) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        if ('' === $host || str_contains($host, '..')) {
            return false;
        }

        if ('localhost' === $host || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return false;
        }

        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIpLiteral($host);
        }

        return true;
    }

    private function isPublicIpLiteral(string $ip): bool
    {
        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (IpUtils::checkIp($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }
}
