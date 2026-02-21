<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;

/**
 * @psalm-api
 */
final readonly class TwoFactorEventPublisher implements
    TwoFactorEventPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private AuthTokenFactoryInterface $authTokenFactory,
    ) {
    }

    #[\Override]
    public function publishEnabled(string $userId, string $email): void
    {
        $this->eventBus->publish(
            new TwoFactorEnabledEvent(
                $userId,
                $email,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    #[\Override]
    public function publishDisabled(string $userId, string $email): void
    {
        $this->eventBus->publish(
            new TwoFactorDisabledEvent(
                $userId,
                $email,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    #[\Override]
    public function publishCompleted(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        ?string $verificationMethod
    ): void {
        $this->eventBus->publish(
            new TwoFactorCompletedEvent(
                $userId,
                $sessionId,
                $ipAddress,
                $userAgent,
                (string) $verificationMethod,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    #[\Override]
    public function publishFailed(
        string $pendingSessionId,
        string $ipAddress,
        string $reason
    ): void {
        $this->eventBus->publish(
            new TwoFactorFailedEvent(
                $pendingSessionId,
                $ipAddress,
                $reason,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    #[\Override]
    public function publishRecoveryCodeUsed(
        string $userId,
        int $remainingCount
    ): void {
        $this->eventBus->publish(
            new RecoveryCodeUsedEvent(
                $userId,
                $remainingCount,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    #[\Override]
    public function publishAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount
    ): void {
        $this->eventBus->publish(
            new AllSessionsRevokedEvent(
                $userId,
                $reason,
                $revokedCount,
                $this->authTokenFactory->nextEventId()
            )
        );
    }
}
