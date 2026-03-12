<?php

declare(strict_types=1);

namespace App\User\Application\Processor\EventPublisher;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\Generator\EventIdGeneratorInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\SessionRevokedEvent;

/**
 * @psalm-api
 */
final readonly class SessionEvents implements SessionEventsInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdGeneratorInterface $eventIdGenerator,
    ) {
    }

    #[\Override]
    public function publishSessionRevoked(
        string $userId,
        string $sessionId,
        string $reason
    ): void {
        $this->eventBus->publish(new SessionRevokedEvent(
            $userId,
            $sessionId,
            $reason,
            $this->eventIdGenerator->generate()
        ));
    }

    #[\Override]
    public function publishAllSessionsRevoked(
        string $userId,
        string $reason,
        int $revokedCount
    ): void {
        $this->eventBus->publish(new AllSessionsRevokedEvent(
            $userId,
            $reason,
            $revokedCount,
            $this->eventIdGenerator->generate()
        ));
    }
}
