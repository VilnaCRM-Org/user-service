<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final readonly class ApiRateLimitOAuthSocialTargetResolver
{
    private const INITIATE_PATTERN = '#^/api/auth/social/[^/]+$#';
    private const CALLBACK_PATTERN = '#^/api/auth/social/[^/]+/callback$#';

    /**
     * @return array{name: 'oauth_social_callback'|'oauth_social_initiate', key: string}|null
     */
    public function resolve(Request $request): ?array
    {
        if (strtoupper($request->getMethod()) !== 'GET') {
            return null;
        }

        $path = $request->getPathInfo();
        if (preg_match(self::CALLBACK_PATTERN, $path) === 1) {
            return [
                'name' => 'oauth_social_callback',
                'key' => $this->buildIpKey($request),
            ];
        }

        if (preg_match(self::INITIATE_PATTERN, $path) === 1) {
            return [
                'name' => 'oauth_social_initiate',
                'key' => $this->buildIpKey($request),
            ];
        }

        return null;
    }

    private function buildIpKey(Request $request): string
    {
        $ipAddress = $request->getClientIp() ?? '0.0.0.0';
        return sprintf('ip:%s', $ipAddress);
    }
}
