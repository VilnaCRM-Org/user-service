<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class AuthCookieFactory implements AuthCookieFactoryInterface
{
    public const COOKIE_NAME = '__Host-auth_token';

    #[\Override]
    public function create(
        string $token,
        bool $rememberMe,
        int $standardMaxAge,
        int $rememberMeMaxAge
    ): Cookie {
        $maxAge = $rememberMe ? $rememberMeMaxAge : $standardMaxAge;

        return Cookie::create(
            self::COOKIE_NAME,
            $token,
            (new DateTimeImmutable())->modify(sprintf('+%d seconds', $maxAge))
        )
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }
}
