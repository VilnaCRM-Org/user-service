<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Async event dispatcher - sends domain events to message queue
 *
 * Implementation must be resilient: failures are logged and tracked via metrics
 * but never thrown to caller. This ensures the main request always succeeds
 * regardless of queue availability (AP from CAP theorem).
 */
interface AsyncEventDispatcherInterface
{
    /**
     * Dispatch events to async queue
     *
     * @return bool True if dispatch succeeded, false if failed (handled gracefully)
     */
    public function dispatch(DomainEvent ...$events): bool;
}
