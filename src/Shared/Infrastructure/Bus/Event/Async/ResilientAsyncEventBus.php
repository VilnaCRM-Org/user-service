<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Bus\Event\AsyncEventDispatcherInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;

/**
 * Resilient async event bus implementation
 *
 * Dispatches all domain events to async queue for eventual consistency.
 * Never throws exceptions - failures are handled gracefully with logging and metrics.
 *
 * Follows AP from CAP theorem: availability over consistency.
 */
final readonly class ResilientAsyncEventBus implements EventBusInterface
{
    public function __construct(
        private AsyncEventDispatcherInterface $dispatcher
    ) {
    }

    #[\Override]
    public function publish(DomainEvent ...$events): void
    {
        $this->dispatcher->dispatch(...$events);
    }
}
