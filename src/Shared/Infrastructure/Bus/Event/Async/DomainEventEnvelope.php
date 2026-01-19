<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Envelope for domain events sent via Symfony Messenger
 *
 * Contains serialized event data for transport over SQS.
 * Uses DomainEvent::toPrimitives() for serialization and
 * DomainEvent::fromPrimitives() for deserialization.
 */
final readonly class DomainEventEnvelope
{
    /**
     * @param class-string<DomainEvent> $eventClass
     * @param array<string, string|object> $body
     */
    public function __construct(
        private string $eventClass,
        private array $body,
        private string $eventId,
        private string $occurredOn
    ) {
    }

    public static function fromEvent(DomainEvent $event): self
    {
        return new self(
            eventClass: $event::class,
            body: $event->toPrimitives(),
            eventId: $event->eventId(),
            occurredOn: $event->occurredOn()
        );
    }

    public function toEvent(): DomainEvent
    {
        $eventClass = $this->eventClass;

        return $eventClass::fromPrimitives(
            $this->body,
            $this->eventId,
            $this->occurredOn
        );
    }
}
