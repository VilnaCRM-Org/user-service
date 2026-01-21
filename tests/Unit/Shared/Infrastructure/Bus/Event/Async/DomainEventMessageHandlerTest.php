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

final class DomainEventMessageHandlerTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private EventSubscriberFailureMetricFactory $metricFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->metricFactory = new EventSubscriberFailureMetricFactory(new MetricDimensionsFactory());
    }

    public function testExecutesSubscriberForMatchingEvent(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $handler = new DomainEventMessageHandler(
            [$subscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        self::assertCount(1, $subscriber->handled());
        self::assertSame('event-456', $subscriber->handled()[0]->eventId());
    }

    public function testDoesNotExecuteSubscriberForNonMatchingEvent(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $handler = new DomainEventMessageHandler(
            [$subscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        // Create envelope with OtherDomainEvent which TestDomainEventSubscriber doesn't subscribe to
        $otherEvent = new Stub\OtherDomainEvent('some-data', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($otherEvent);

        $handler($envelope);

        // TestDomainEventSubscriber only subscribes to TestDomainEvent, not OtherDomainEvent
        self::assertCount(0, $subscriber->handled());
    }

    public function testCatchesSubscriberExceptionAndContinues(): void
    {
        $failingSubscriber = new TestDomainEventSubscriber();
        $failingSubscriber->failOnNextCall();

        $successSubscriber = new TestDomainEventSubscriber();

        $handler = new DomainEventMessageHandler(
            [$failingSubscriber, $successSubscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        // Should not throw
        $handler($envelope);

        // First subscriber failed, but second should still be executed
        self::assertCount(0, $failingSubscriber->handled());
        self::assertCount(1, $successSubscriber->handled());
    }

    public function testEmitsMetricOnSubscriberFailure(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

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

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        // Should not throw
        $handler($envelope);
        self::assertTrue(true);
    }

    public function testExecutesMultipleSubscribersForSameEvent(): void
    {
        $subscriber1 = new TestDomainEventSubscriber();
        $subscriber2 = new TestDomainEventSubscriber();

        $handler = new DomainEventMessageHandler(
            [$subscriber1, $subscriber2],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

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

        $handler = new DomainEventMessageHandler(
            [$subscriber1, $subscriber2],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

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

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        // Should not throw even when metric emission fails
        $handler($envelope);
        self::assertTrue(true);
    }

    public function testLogsDebugMessageWithEventContext(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $capturedContext = [];

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('debug')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Processing domain event from queue') {
                    $capturedContext = $context;
                }
            });

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $handler($envelope);

        self::assertSame($event->eventId(), $capturedContext['event_id']);
        self::assertSame(TestDomainEvent::class, $capturedContext['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $capturedContext['event_name']);
    }

    public function testContinuesToNextSubscriberWhenFirstDoesNotMatch(): void
    {
        // OtherDomainEventSubscriber only subscribes to OtherDomainEvent
        $nonMatchingSubscriber = new Stub\OtherDomainEventSubscriber();
        // TestDomainEventSubscriber subscribes to TestDomainEvent
        $matchingSubscriber = new TestDomainEventSubscriber();

        $handler = new DomainEventMessageHandler(
            [$nonMatchingSubscriber, $matchingSubscriber],
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        // Send TestDomainEvent - first subscriber doesn't match, second does
        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler($envelope);

        // The loop must CONTINUE (not break) to reach the matching subscriber
        self::assertCount(1, $matchingSubscriber->handled());
        self::assertSame('event-456', $matchingSubscriber->handled()[0]->eventId());
    }

    public function testLogsSubscriberExecutionWithContext(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $capturedContext = [];

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('debug')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Subscriber executed successfully') {
                    $capturedContext = $context;
                }
            });

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $handler($envelope);

        self::assertSame(TestDomainEventSubscriber::class, $capturedContext['subscriber']);
        self::assertSame($event->eventId(), $capturedContext['event_id']);
    }

    public function testLogsErrorWithFullContextOnSubscriberFailure(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();
        $capturedContext = [];

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('error')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Domain event subscriber execution failed in worker') {
                    $capturedContext = $context;
                }
            });
        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $handler($envelope);

        self::assertSame(TestDomainEventSubscriber::class, $capturedContext['subscriber']);
        self::assertSame($event->eventId(), $capturedContext['event_id']);
        self::assertSame(TestDomainEvent::class, $capturedContext['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $capturedContext['event_name']);
        self::assertSame('Subscriber failed', $capturedContext['error']);
        self::assertSame(\RuntimeException::class, $capturedContext['exception_class']);
    }

    public function testLogsWarningWithContextWhenMetricEmissionFails(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();
        $this->metricsEmitter->failOnNextCall();
        $capturedContext = [];

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('warning')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Failed to emit subscriber failure metric') {
                    $capturedContext = $context;
                }
            });
        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $envelope = DomainEventEnvelope::fromEvent($event);

        $handler = new DomainEventMessageHandler(
            [$subscriber],
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $handler($envelope);

        self::assertArrayHasKey('error', $capturedContext);
        self::assertSame('Metric emission failed', $capturedContext['error']);
    }
}
