<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-api
 */
final readonly class ClearAuthCookieResponseFactory implements
    ClearAuthCookieResponseFactoryInterface
{
    #[\Override]
    public function create(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(
            new Cookie(
                AuthCookieFactory::COOKIE_NAME,
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
