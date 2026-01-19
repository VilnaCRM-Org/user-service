<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Bus\Event\AsyncEventDispatcherInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Messenger-based async event dispatcher
 *
 * Layer 1 Resilience: If queue send fails, log error + emit metric, return false.
 * Never throws exceptions - preserves main request success (AP from CAP theorem).
 */
final readonly class MessengerAsyncEventDispatcher implements AsyncEventDispatcherInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {
    }

    public function dispatch(DomainEvent ...$events): bool
    {
        $allSucceeded = true;

        foreach ($events as $event) {
            if (!$this->dispatchSingle($event)) {
                $allSucceeded = false;
            }
        }

        return $allSucceeded;
    }

    private function dispatchSingle(DomainEvent $event): bool
    {
        try {
            $envelope = DomainEventEnvelope::fromEvent($event);
            $this->messageBus->dispatch($envelope);

            $this->logger->debug('Domain event dispatched to queue', [
                'event_id' => $event->eventId(),
                'event_type' => $event::class,
                'event_name' => $event::eventName(),
            ]);

            return true;
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Failed to dispatch domain event to queue (Layer 1)', [
                'event_id' => $event->eventId(),
                'event_type' => $event::class,
                'event_name' => $event::eventName(),
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            return false;
        }
    }
}
