<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;

final readonly class RefreshTokenEventPublisher implements
    RefreshTokenEventPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private AuthTokenFactoryInterface $authTokenFactory
    ) {
    }

    public function publishRotated(string $sessionId, string $userId): void
    {
        $this->eventBus->publish(
            new RefreshTokenRotatedEvent(
                $sessionId,
                $userId,
                $this->authTokenFactory->nextEventId()
            )
        );
    }

    public function publishTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason
    ): void {
        $this->eventBus->publish(
            new RefreshTokenTheftDetectedEvent(
                $sessionId,
                $userId,
                $ipAddress,
                $reason,
                $this->authTokenFactory->nextEventId()
            )
        );
    }
}
