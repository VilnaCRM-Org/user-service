<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\HttpFoundation\Cookie;

interface AuthCookieFactoryInterface
{
    public function create(
        string $token,
        bool $rememberMe,
        int $standardMaxAge,
        int $rememberMeMaxAge
    ): Cookie;
}
