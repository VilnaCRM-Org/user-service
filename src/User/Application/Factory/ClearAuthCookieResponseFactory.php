<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final readonly class ClearAuthCookieResponseFactory
{
    public function create(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(
            new Cookie(
                '__Host-auth_token',
                '',
                1,
                '/',
                null,
                true,
                true,
                false,
                'lax'
            )
        );

        return $response;
    }
}
