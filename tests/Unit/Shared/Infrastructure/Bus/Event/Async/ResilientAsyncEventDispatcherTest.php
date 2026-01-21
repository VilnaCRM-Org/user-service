<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Observability\Metric\SqsDispatchFailureMetric;
use App\Shared\Infrastructure\Bus\Event\Async\ResilientAsyncEventDispatcher;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Shared\Infrastructure\Observability\Factory\SqsDispatchFailureMetricFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ResilientAsyncEventDispatcherTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private SqsDispatchFailureMetricFactory $metricFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->metricFactory = new SqsDispatchFailureMetricFactory(new MetricDimensionsFactory());
    }

    public function testDispatchesEventToMessageBus(): void
    {
        $dispatchedMessages = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$dispatchedMessages) {
                $dispatchedMessages[] = $message;

                return new Envelope($message);
            });

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $result = $dispatcher->dispatch($event);

        self::assertTrue($result);
        self::assertCount(1, $dispatchedMessages);
    }

    public function testReturnsTrueOnSuccessfulDispatch(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static fn ($message) => new Envelope($message));

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $result = $dispatcher->dispatch($event);

        self::assertTrue($result);
    }

    public function testReturnsFalseOnDispatchFailure(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $result = $dispatcher->dispatch($event);

        self::assertFalse($result);
    }

    public function testNeverThrowsExceptionOnFailure(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');

        // Should not throw
        $dispatcher->dispatch($event);
        self::assertTrue(true);
    }

    public function testEmitsMetricOnDispatchFailure(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        self::assertSame(1, $this->metricsEmitter->count());
        $emittedMetric = $this->metricsEmitter->emitted()->all()[0];
        self::assertInstanceOf(SqsDispatchFailureMetric::class, $emittedMetric);
        self::assertSame('SqsDispatchFailures', $emittedMetric->name());
    }

    public function testDispatchesMultipleEvents(): void
    {
        $dispatchCount = 0;
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$dispatchCount) {
                $dispatchCount++;

                return new Envelope($message);
            });

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event1 = new TestDomainEvent('aggregate-1', 'event-1');
        $event2 = new TestDomainEvent('aggregate-2', 'event-2');
        $result = $dispatcher->dispatch($event1, $event2);

        self::assertTrue($result);
        self::assertSame(2, $dispatchCount);
    }

    public function testReturnsFalseIfAnyEventFailsToDispatch(): void
    {
        $callCount = 0;
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$callCount) {
                $callCount++;
                if ($callCount === 2) {
                    throw new \RuntimeException('Second event failed');
                }

                return new Envelope($message);
            });

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event1 = new TestDomainEvent('aggregate-1', 'event-1');
        $event2 = new TestDomainEvent('aggregate-2', 'event-2');
        $result = $dispatcher->dispatch($event1, $event2);

        self::assertFalse($result);
        self::assertSame(1, $this->metricsEmitter->count());
    }

    public function testContinuesWhenMetricEmissionFails(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));

        $this->metricsEmitter->failOnNextCall();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');

        // Should not throw even when metric emission fails
        $result = $dispatcher->dispatch($event);
        self::assertFalse($result);
    }

    public function testLogsDebugMessageOnSuccessfulDispatch(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static fn ($message) => new Envelope($message));

        $capturedContext = [];
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('debug')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Domain event dispatched to async queue') {
                    $capturedContext = $context;
                }
            });

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        self::assertSame($event->eventId(), $capturedContext['event_id']);
        self::assertSame(TestDomainEvent::class, $capturedContext['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $capturedContext['event_name']);
    }

    public function testLogsErrorWithContextOnDispatchFailure(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));

        $capturedContext = [];
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('error')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Failed to dispatch domain event to async queue') {
                    $capturedContext = $context;
                }
            });

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        self::assertSame($event->eventId(), $capturedContext['event_id']);
        self::assertSame(TestDomainEvent::class, $capturedContext['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $capturedContext['event_name']);
        self::assertSame('SQS unavailable', $capturedContext['error']);
        self::assertSame(\RuntimeException::class, $capturedContext['exception_class']);
    }

    public function testLogsWarningWhenMetricEmissionFails(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));

        $this->metricsEmitter->failOnNextCall();

        $capturedContext = [];
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('warning')
            ->willReturnCallback(static function (string $message, array $context) use (&$capturedContext): void {
                if ($message === 'Failed to emit SQS dispatch failure metric') {
                    $capturedContext = $context;
                }
            });

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        self::assertArrayHasKey('error', $capturedContext);
        self::assertSame('Metric emission failed', $capturedContext['error']);
    }
}
