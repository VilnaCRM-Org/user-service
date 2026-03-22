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

    #[\Override]
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
            $this->messageBus->dispatch($this->buildEnvelope($event));
            $this->logDispatchSuccess($event);

            return true;
        } catch (\Throwable $exception) {
            $this->logDispatchFailure($event, $exception);

            return false;
        }
    }

    private function buildEnvelope(DomainEvent $event): DomainEventEnvelope
    {
        return new DomainEventEnvelope(
            eventClass: $event::class,
            body: $event->toPrimitives(),
            eventId: $event->eventId(),
            occurredOn: $event->occurredOn()
        );
    }

    private function logDispatchSuccess(DomainEvent $event): void
    {
        $this->logger->debug('Domain event dispatched to queue', [
            'event_id' => $event->eventId(),
            'event_type' => $event::class,
            'event_name' => $event::eventName(),
        ]);
    }

    private function logDispatchFailure(
        DomainEvent $event,
        ExceptionInterface $exception
    ): void {
        $this->logger->error('Failed to dispatch domain event to queue (Layer 1)', [
            'event_id' => $event->eventId(),
            'event_type' => $event::class,
            'event_name' => $event::eventName(),
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);
    }
}
