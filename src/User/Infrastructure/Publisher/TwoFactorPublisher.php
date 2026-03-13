<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;

final readonly class TwoFactorPublisher implements TwoFactorPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory,
    ) {
    }

    #[\Override]
    public function publishEnabled(string $userId, string $email): void
    {
        $this->eventBus->publish(new TwoFactorEnabledEvent(
            $userId,
            $email,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishDisabled(string $userId, string $email): void
    {
        $this->eventBus->publish(new TwoFactorDisabledEvent(
            $userId,
            $email,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishCompleted(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        ?string $verificationMethod
    ): void {
        $this->eventBus->publish(new TwoFactorCompletedEvent(
            $userId,
            $sessionId,
            $ipAddress,
            $userAgent,
            (string) $verificationMethod,
            $this->eventIdFactory->generate()
        ));
        $this->eventBus->publish(new UserSignedInEvent(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            true,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishFailed(
        string $pendingSessionId,
        string $ipAddress,
        string $reason
    ): void {
        $this->eventBus->publish(new TwoFactorFailedEvent(
            $pendingSessionId,
            $ipAddress,
            $reason,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishRecoveryCodeUsed(string $userId, int $remainingCount): void
    {
        $this->eventBus->publish(new RecoveryCodeUsedEvent(
            $userId,
            $remainingCount,
            $this->eventIdFactory->generate()
        ));
    }
}
