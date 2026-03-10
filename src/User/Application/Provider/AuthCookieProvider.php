<?php

declare(strict_types=1);

namespace App\User\Application\Provider;

use App\User\Application\Factory\AuthCookieFactoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-api
 */
final readonly class AuthCookieProvider implements AuthCookieProviderInterface
{
    public function __construct(
        private AuthCookieFactoryInterface $authCookieFactory,
        private int $standardCookieMaxAge = 900,
        private int $rememberMeCookieMaxAge = 2592000,
    ) {
    }

    #[\Override]
    public function attach(
        Response $response,
        string $accessToken,
        bool $rememberMe
    ): void {
        if ($accessToken === '') {
            return;
        }

        $response->headers->setCookie(
            $this->authCookieFactory->create(
                $accessToken,
                $rememberMe,
                $this->standardCookieMaxAge,
                $this->rememberMeCookieMaxAge,
                new DateTimeImmutable()
            )
        );
    }
}
