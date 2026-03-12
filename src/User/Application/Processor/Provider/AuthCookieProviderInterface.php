<?php

declare(strict_types=1);

namespace App\User\Application\Processor\Provider;

use Symfony\Component\HttpFoundation\Response;

interface AuthCookieProviderInterface
{
    public function attach(
        Response $response,
        string $accessToken,
        bool $rememberMe
    ): void;
}
