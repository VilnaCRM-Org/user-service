<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PasskeyAuthenticationIssuer
{
    public function __construct(
        private PasskeyAuthenticationResultFactory $authenticationResultFactory,
        private SignInPublisherInterface $signInPublisher,
        private LoggerInterface $logger
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

        $this->publishSignedIn($user, $result, $ipAddress, $userAgent);

        return $result;
    }

    private function publishSignedIn(
        User $user,
        PasskeyAuthenticationResult $result,
        string $ipAddress,
        string $userAgent
    ): void {
        try {
            $this->signInPublisher->publishSignedIn(
                $user->getId(),
                $user->getEmail(),
                $result->getSessionId(),
                $ipAddress,
                $userAgent,
                false
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Passkey sign-in event dispatch failed.', [
                'exception' => $exception,
                'ip_address' => $ipAddress,
                'session_id' => $result->getSessionId(),
                'user_agent' => $userAgent,
                'user_id' => $user->getId(),
            ]);
        }
    }
}
