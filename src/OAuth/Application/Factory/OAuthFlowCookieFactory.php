<?php

declare(strict_types=1);

namespace App\OAuth\Application\Factory;

use Symfony\Component\HttpFoundation\Cookie;

/**
 * @psalm-api
 */
final readonly class OAuthFlowCookieFactory
{
    public const COOKIE_NAME = 'oauth_flow_binding';

    public function __construct(
        private int $ttlSeconds = 600,
    ) {
    }

    public function create(string $flowBindingToken): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue($flowBindingToken)
            ->withExpires(time() + $this->ttlSeconds)
            ->withPath('/api/auth/social')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }
}
