<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Factory\Event\SignInEventFactoryInterface;
use App\User\Domain\Factory\Event\TwoFactorEventFactoryInterface;

final readonly class TwoFactorPublisher implements TwoFactorPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory,
        private TwoFactorEventFactoryInterface $twoFactorEventFactory,
        private SignInEventFactoryInterface $signInEventFactory,
    ) {
    }

    #[\Override]
    public function publishEnabled(string $userId, string $email): void
    {
        $this->eventBus->publish($this->twoFactorEventFactory->createEnabled(
            $userId,
            $email,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishDisabled(string $userId, string $email): void
    {
        $this->eventBus->publish($this->twoFactorEventFactory->createDisabled(
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
        $this->eventBus->publish($this->twoFactorEventFactory->createCompleted(
            $userId,
            $sessionId,
            $ipAddress,
            $userAgent,
            (string) $verificationMethod,
            $this->eventIdFactory->generate()
        ));
        $this->eventBus->publish($this->signInEventFactory->createSignedIn(
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
        $this->eventBus->publish($this->twoFactorEventFactory->createFailed(
            $pendingSessionId,
            $ipAddress,
            $reason,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishRecoveryCodeUsed(string $userId, int $remainingCount): void
    {
        $this->eventBus->publish($this->twoFactorEventFactory->createRecoveryCodeUsed(
            $userId,
            $remainingCount,
            $this->eventIdFactory->generate()
        ));
    }
}
