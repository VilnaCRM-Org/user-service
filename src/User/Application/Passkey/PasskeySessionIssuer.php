<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;

final readonly class PasskeySessionIssuer
{
    public function __construct(
        private IssuedSessionFactoryInterface $issuedSessionFactory,
        private SignInPublisherInterface $signInPublisher
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

        $this->signInPublisher->publishSignedIn(
            $user->getId(),
            $user->getEmail(),
            $issuedSession->sessionId,
            $ipAddress,
            $userAgent,
            false
        );

        return new PasskeyAuthenticationResult(
            $issuedSession->accessToken,
            $issuedSession->refreshToken,
            $rememberMe
        );
    }
}
