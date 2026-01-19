<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles domain events from async queue
 *
 * Layer 2 Resilience: If any subscriber fails, log error and continue with others.
 * Message is acknowledged after processing all subscribers (no retry to avoid loops).
 */
final readonly class DomainEventMessageHandler
{
    /**
     * @param iterable<DomainEventSubscriberInterface> $subscribers
     */
    public function __construct(
        private iterable $subscribers,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(DomainEventEnvelope $envelope): void
    {
        $event = $envelope->toEvent();
        $eventClass = $event::class;

        $this->logger->debug('Processing domain event from queue', [
            'event_id' => $event->eventId(),
            'event_type' => $eventClass,
            'event_name' => $event::eventName(),
        ]);

        foreach ($this->subscribers as $subscriber) {
            if (!$this->subscriberHandlesEvent($subscriber, $eventClass)) {
                continue;
            }

            $this->executeSubscriber($subscriber, $event);
        }
    }

    /**
     * @param class-string<DomainEvent> $eventClass
     */
    private function subscriberHandlesEvent(
        DomainEventSubscriberInterface $subscriber,
        string $eventClass
    ): bool {
        return in_array($eventClass, $subscriber->subscribedTo(), true);
    }

    private function executeSubscriber(
        DomainEventSubscriberInterface $subscriber,
        DomainEvent $event
    ): void {
        try {
            ($subscriber)($event);

            $this->logger->debug('Subscriber executed successfully', [
                'subscriber' => $subscriber::class,
                'event_id' => $event->eventId(),
            ]);
        } catch (\Throwable $exception) {
            $this->handleSubscriberFailure($subscriber, $event, $exception);
        }
    }

    private function handleSubscriberFailure(
        DomainEventSubscriberInterface $subscriber,
        DomainEvent $event,
        \Throwable $exception
    ): void {
        // Note: Do NOT log payload - it may contain PII (GDPR/SOC2 compliance)
        $this->logger->error('Domain event subscriber execution failed in worker', [
            'subscriber' => $subscriber::class,
            'event_id' => $event->eventId(),
            'event_type' => $event::class,
            'event_name' => $event::eventName(),
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ]);
    }
}
