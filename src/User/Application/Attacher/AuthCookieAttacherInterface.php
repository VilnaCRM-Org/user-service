<?php

declare(strict_types=1);

namespace App\User\Application\Attacher;

use Symfony\Component\HttpFoundation\Response;

interface AuthCookieAttacherInterface
{
    public function attach(
        Response $response,
        string $accessToken,
        bool $rememberMe
    ): void;
}
