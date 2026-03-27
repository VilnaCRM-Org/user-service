<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Publisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Factory\Event\SessionRevocationEventFactoryInterface;

/**
 * @psalm-api
 */
final readonly class SessionPublisher implements SessionPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory,
        private SessionRevocationEventFactoryInterface $sessionRevocationEventFactory,
    ) {
    }

    #[\Override]
    public function publishSessionRevoked(
        string $userId,
        string $sessionId,
        string $reason
    ): void {
        $this->eventBus->publish($this->sessionRevocationEventFactory->createSessionRevoked(
            $userId,
            $sessionId,
            $reason,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount
    ): void {
        $this->eventBus->publish($this->sessionRevocationEventFactory->createAllSessionsRevoked(
            $userId,
            $reason,
            $revokedCount,
            $this->eventIdFactory->generate()
        ));
    }
}
