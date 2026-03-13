<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @psalm-api
 */
final readonly class AuthCookieFactory implements AuthCookieFactoryInterface
{
    public const COOKIE_NAME = '__Host-auth_token';

    public function __construct(
        private int $standardCookieMaxAge = 900,
        private int $rememberMeCookieMaxAge = 2592000,
    ) {
    }

    #[\Override]
    public function create(
        string $token,
        bool $rememberMe
    ): Cookie {
        $maxAge = $rememberMe ? $this->rememberMeCookieMaxAge : $this->standardCookieMaxAge;
        $now = new DateTimeImmutable();

        return Cookie::create(
            self::COOKIE_NAME,
            $token,
            $now->modify(sprintf('+%d seconds', $maxAge))
        )
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }
}
