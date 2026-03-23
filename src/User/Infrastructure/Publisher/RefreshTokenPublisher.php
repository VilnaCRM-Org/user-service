<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;

/**
 * @psalm-api
 */
final readonly class RefreshTokenPublisher implements RefreshTokenPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory,
    ) {
    }

    #[\Override]
    public function publishTokenRotated(string $sessionId, string $userId): void
    {
        $this->eventBus->publish(new RefreshTokenRotatedEvent(
            $sessionId,
            $userId,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishTheftDetected(
        string $sessionId,
        string $userId,
        string $ipAddress,
        string $reason
    ): void {
        $this->eventBus->publish(new RefreshTokenTheftDetectedEvent(
            $sessionId,
            $userId,
            $ipAddress,
            $reason,
            $this->eventIdFactory->generate()
        ));
    }
}
