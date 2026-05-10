<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Observability\Metric\EventSubscriberFailureMetric;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelope;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventMessageHandler;
use App\Shared\Infrastructure\Observability\Factory\EventSubscriberFailureMetricFactory;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\NullLogger;

final class DomainEventMessageHandlerExecutionTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private EventSubscriberFailureMetricFactory $metricFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->metricFactory = new EventSubscriberFailureMetricFactory(
            new MetricDimensionsFactory()
        );
    }

    public function testExecutesSubscriberForMatchingEvent(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $handler = $this->createHandler([$subscriber]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertCount(1, $subscriber->handled());
        self::assertSame('event-456', $subscriber->handled()[0]->eventId());
    }

    public function testDoesNotExecuteSubscriberForNonMatchingEvent(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $handler = $this->createHandler([$subscriber]);

        $otherEvent = new Stub\OtherDomainEvent('some-data', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($otherEvent);

        $handler($envelope);

        self::assertCount(0, $subscriber->handled());
    }

    public function testCatchesSubscriberExceptionAndContinues(): void
    {
        $failingSubscriber = new TestDomainEventSubscriber();
        $failingSubscriber->failOnNextCall();

        $successSubscriber = new TestDomainEventSubscriber();

        $handler = $this->createHandler([$failingSubscriber, $successSubscriber]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertCount(0, $failingSubscriber->handled());
        self::assertCount(1, $successSubscriber->handled());
    }

    public function testEmitsMetricOnSubscriberFailure(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();

        $handler = $this->createHandler([$subscriber]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertSame(1, $this->metricsEmitter->count());
        $emittedMetric = $this->metricsEmitter->emitted()->all()[0];
        self::assertInstanceOf(EventSubscriberFailureMetric::class, $emittedMetric);
        self::assertSame('EventSubscriberFailures', $emittedMetric->name());
    }

    public function testNeverThrowsException(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();

        $handler = $this->createHandler([$subscriber]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertTrue(true);
    }

    public function testExecutesMultipleSubscribersForSameEvent(): void
    {
        $subscriber1 = new TestDomainEventSubscriber();
        $subscriber2 = new TestDomainEventSubscriber();

        $handler = $this->createHandler([$subscriber1, $subscriber2]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertCount(1, $subscriber1->handled());
        self::assertCount(1, $subscriber2->handled());
    }

    public function testEmitsMetricForEachFailingSubscriber(): void
    {
        $subscriber1 = new TestDomainEventSubscriber();
        $subscriber1->failOnNextCall();
        $subscriber2 = new TestDomainEventSubscriber();
        $subscriber2->failOnNextCall();

        $handler = $this->createHandler([$subscriber1, $subscriber2]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertSame(2, $this->metricsEmitter->count());
    }

    public function testContinuesWhenMetricEmissionFails(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();

        $this->metricsEmitter->failOnNextCall();

        $handler = $this->createHandler([$subscriber]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);
        self::assertTrue(true);
    }

    public function testContinuesToNextSubscriberWhenFirstDoesNotMatch(): void
    {
        $nonMatchingSubscriber = new Stub\OtherDomainEventSubscriber();
        $matchingSubscriber = new TestDomainEventSubscriber();

        $handler = $this->createHandler([$nonMatchingSubscriber, $matchingSubscriber]);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertCount(1, $matchingSubscriber->handled());
        self::assertSame('event-456', $matchingSubscriber->handled()[0]->eventId());
    }

    /**
     * @param array<int, TestDomainEventSubscriber|Stub\OtherDomainEventSubscriber> $subscribers
     */
    private function createHandler(array $subscribers): DomainEventMessageHandler
    {
        return new DomainEventMessageHandler(
            $subscribers,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );
    }
}
