<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;

final readonly class PasskeyAuthenticationIssuer
{
    public function __construct(
        private PasskeyAuthenticationResultFactory $authenticationResultFactory,
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
        $result = $this->authenticationResultFactory->issue(
            $user,
            $rememberMe,
            $ipAddress,
            $userAgent,
            $issuedAt
        );

        $this->signInPublisher->publishSignedIn(
            $user->getId(),
            $user->getEmail(),
            $result->getSessionId(),
            $ipAddress,
            $userAgent,
            false
        );

        return $result;
    }
}
