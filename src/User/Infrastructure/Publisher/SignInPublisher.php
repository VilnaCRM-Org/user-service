<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;

final readonly class SignInPublisher implements SignInPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory,
    ) {
    }

    #[\Override]
    public function publishSignedIn(
        string $userId,
        string $email,
        string $sessionId,
        string $ipAddress,
        string $userAgent,
        bool $twoFactorUsed
    ): void {
        $this->eventBus->publish(new UserSignedInEvent(
            $userId,
            $email,
            $sessionId,
            $ipAddress,
            $userAgent,
            $twoFactorUsed,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishFailed(
        string $email,
        string $ipAddress,
        string $userAgent,
        string $reason
    ): void {
        $this->eventBus->publish(new SignInFailedEvent(
            $email,
            $ipAddress,
            $userAgent,
            $reason,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishLockedOut(
        string $email,
        int $failedAttempts,
        int $lockoutDurationSeconds
    ): void {
        $this->eventBus->publish(new AccountLockedOutEvent(
            $email,
            $failedAttempts,
            $lockoutDurationSeconds,
            $this->eventIdFactory->generate()
        ));
    }
}
