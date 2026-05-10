<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;

final readonly class DomainEventEnvelopeFactory
{
    public function createFromEvent(DomainEvent $event): DomainEventEnvelope
    {
        return new DomainEventEnvelope(
            eventClass: $event::class,
            body: $event->toPrimitives(),
            eventId: $event->eventId(),
            occurredOn: $event->occurredOn()
        );
    }
}
