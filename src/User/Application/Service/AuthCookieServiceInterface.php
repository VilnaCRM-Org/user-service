<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use Symfony\Component\HttpFoundation\Response;

interface AuthCookieServiceInterface
{
    public function attach(
        Response $response,
        string $accessToken,
        bool $rememberMe
    ): void;
}
