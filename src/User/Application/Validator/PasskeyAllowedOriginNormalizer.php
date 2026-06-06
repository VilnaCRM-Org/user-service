<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use function filter_var;
use function in_array;

use InvalidArgumentException;

use function is_int;
use function is_string;
use function parse_url;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;

final class PasskeyAllowedOriginNormalizer
{
    private const LOCALHOST_HOSTS = ['localhost', '127.0.0.1', '::1'];
    private const NON_BROWSER_ORIGIN_COMPONENTS = [
        PHP_URL_QUERY,
        PHP_URL_FRAGMENT,
        PHP_URL_USER,
        PHP_URL_PASS,
    ];

    public function normalize(string $origin, string $appEnv, string $rpId): string
    {
        if (str_contains($origin, '*')) {
            throw new InvalidArgumentException('Passkey allowed origins cannot contain wildcards.');
        }

        $browserOrigin = $this->parseBrowserOrigin($origin);
        $scheme = $browserOrigin['scheme'];
        $normalizedHost = $browserOrigin['host'];

        $this->assertOriginSchemeAllowedForEnvironment($scheme, $normalizedHost, $appEnv);
        $this->assertProductionOriginHost($normalizedHost, $rpId, $appEnv);

        return sprintf(
            '%s://%s%s',
            $scheme,
            $this->formatOriginHost($normalizedHost),
            $browserOrigin['port'] === null ? '' : sprintf(':%d', $browserOrigin['port'])
        );
    }

    /**
     * @return array{scheme: string, host: string, port: int|null}
     */
    private function parseBrowserOrigin(string $origin): array
    {
        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $host = parse_url($origin, PHP_URL_HOST);
        $port = parse_url($origin, PHP_URL_PORT);

        if (
            !is_string($scheme)
            || !is_string($host)
            || ($port !== null && !is_int($port))
        ) {
            $this->throwInvalidBrowserOrigin();
        }

        $normalizedScheme = strtolower($scheme);
        $this->assertBrowserOriginScheme($normalizedScheme);
        $this->assertOriginHasNoExtraComponents($origin);

        $normalizedHost = $this->normalizeOriginHost($host);
        $this->assertBrowserOriginHost($normalizedHost);

        return [
            'scheme' => $normalizedScheme,
            'host' => $normalizedHost,
            'port' => $port,
        ];
    }

    private function normalizeOriginHost(string $host): string
    {
        $normalizedHost = strtolower($host);
        if (str_starts_with($normalizedHost, '[') && str_ends_with($normalizedHost, ']')) {
            return substr($normalizedHost, 1, -1);
        }

        return $normalizedHost;
    }

    private function formatOriginHost(string $normalizedHost): string
    {
        if (filter_var($normalizedHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return sprintf('[%s]', $normalizedHost);
        }

        return $normalizedHost;
    }

    private function assertBrowserOriginScheme(string $scheme): void
    {
        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->throwInvalidBrowserOrigin();
        }
    }

    private function assertBrowserOriginHost(string $normalizedHost): void
    {
        if (str_starts_with($normalizedHost, '.') || str_ends_with($normalizedHost, '.')) {
            $this->throwInvalidBrowserOrigin();
        }

        if (in_array($normalizedHost, self::LOCALHOST_HOSTS, true)) {
            return;
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
            return;
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            $this->throwInvalidBrowserOrigin();
        }
    }

    private function assertOriginHasNoExtraComponents(string $origin): void
    {
        $path = parse_url($origin, PHP_URL_PATH);
        if ($path !== null && $path !== '') {
            $this->throwInvalidBrowserOrigin();
        }

        foreach (self::NON_BROWSER_ORIGIN_COMPONENTS as $component) {
            if (parse_url($origin, $component) !== null) {
                $this->throwInvalidBrowserOrigin();
            }
        }
    }

    private function assertOriginSchemeAllowedForEnvironment(
        string $scheme,
        string $normalizedHost,
        string $appEnv
    ): void {
        if ($appEnv === 'prod' && $scheme !== 'https') {
            throw new InvalidArgumentException(
                'Production passkey allowed origins must use HTTPS.'
            );
        }

        if ($scheme === 'http' && !in_array($normalizedHost, self::LOCALHOST_HOSTS, true)) {
            throw new InvalidArgumentException(
                'HTTP passkey allowed origins are only allowed for localhost.'
            );
        }
    }

    private function assertProductionOriginHost(
        string $normalizedHost,
        string $rpId,
        string $appEnv
    ): void {
        if ($appEnv !== 'prod') {
            return;
        }

        if (!$this->originHostMatchesRpId($normalizedHost, $rpId)) {
            throw new InvalidArgumentException(sprintf(
                'Production passkey allowed origin host must match the relying party ID %s',
                'or subdomain.'
            ));
        }
    }

    private function originHostMatchesRpId(string $originHost, string $rpId): bool
    {
        $normalizedRpId = strtolower($rpId);

        return $originHost === $normalizedRpId
            || str_ends_with($originHost, sprintf('.%s', $normalizedRpId));
    }

    private function throwInvalidBrowserOrigin(): never
    {
        throw new InvalidArgumentException('Passkey allowed origin must be a browser origin.');
    }
}
