<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

final readonly class PasskeyAuthenticationResultFactory
{
    public function __construct(
        private IssuedSessionFactoryInterface $issuedSessionFactory
    ) {
    }

    public function issue(
        User $user,
        bool $rememberMe,
        string $ipAddress,
        string $userAgent,
        DateTimeImmutable $issuedAt
    ): PasskeyAuthenticationResult {
        $issuedSession = $this->issuedSessionFactory->create(
            $user,
            $ipAddress,
            $userAgent,
            $rememberMe,
            $issuedAt
        );

        return new PasskeyAuthenticationResult(
            $issuedSession->accessToken,
            $issuedSession->refreshToken,
            $rememberMe,
            $issuedSession->sessionId
        );
    }
}
