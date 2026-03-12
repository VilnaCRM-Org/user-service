<?php

declare(strict_types=1);

namespace App\User\Application\Processor\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\Generator\EventIdGeneratorInterface;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;

/** @psalm-suppress UnusedClass */
final readonly class RefreshTokenEvents implements RefreshTokenEventsInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdGeneratorInterface $eventIdGenerator,
    ) {
    }

    #[\Override]
    public function publishRotated(string $sessionId, string $userId): void
    {
        $this->eventBus->publish(new RefreshTokenRotatedEvent(
            $sessionId,
            $userId,
            $this->eventIdGenerator->generate()
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
            $this->eventIdGenerator->generate()
        ));
    }
}
