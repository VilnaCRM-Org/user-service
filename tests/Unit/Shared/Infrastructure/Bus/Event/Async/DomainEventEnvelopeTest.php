<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelope;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\UnitTestCase;

final class DomainEventEnvelopeTest extends UnitTestCase
{
    public function testCreatesEnvelopeFromEvent(): void
    {
        $event = new TestDomainEvent(
            aggregateId: 'aggregate-123',
            eventId: 'event-456'
        );

        $envelope = DomainEventEnvelope::fromEvent($event);

        self::assertSame(TestDomainEvent::class, $envelope->eventClass());
        self::assertSame('event-456', $envelope->eventId());
        self::assertSame(['aggregateId' => 'aggregate-123'], $envelope->body());
    }

    public function testConvertsEnvelopeBackToEvent(): void
    {
        $originalEvent = new TestDomainEvent(
            aggregateId: 'aggregate-123',
            eventId: 'event-456',
            occurredOn: '2024-01-15T10:30:00+00:00'
        );

        $envelope = DomainEventEnvelope::fromEvent($originalEvent);
        $restoredEvent = $envelope->toEvent();

        self::assertInstanceOf(TestDomainEvent::class, $restoredEvent);
        self::assertSame('event-456', $restoredEvent->eventId());
        self::assertSame('2024-01-15T10:30:00+00:00', $restoredEvent->occurredOn());
        self::assertSame('aggregate-123', $restoredEvent->aggregateId());
    }

    public function testPreservesEventIdAndOccurredOn(): void
    {
        $occurredOn = '2024-01-15T10:30:00+00:00';
        $event = new TestDomainEvent(
            aggregateId: 'aggregate-123',
            eventId: 'event-456',
            occurredOn: $occurredOn
        );

        $envelope = DomainEventEnvelope::fromEvent($event);

        self::assertSame('event-456', $envelope->eventId());
        self::assertSame($occurredOn, $envelope->occurredOn());
    }

    public function testRoundTripPreservesAllData(): void
    {
        $event = new TestDomainEvent(
            aggregateId: 'aggregate-123',
            eventId: 'event-456',
            occurredOn: '2024-01-15T10:30:00+00:00'
        );

        $envelope = DomainEventEnvelope::fromEvent($event);
        $restored = $envelope->toEvent();

        self::assertSame($event->eventId(), $restored->eventId());
        self::assertSame($event->occurredOn(), $restored->occurredOn());
        self::assertSame($event->toPrimitives(), $restored->toPrimitives());
    }
}
