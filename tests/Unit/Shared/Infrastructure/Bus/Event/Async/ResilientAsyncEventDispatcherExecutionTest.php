<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Observability\Metric\SqsDispatchFailureMetric;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelopeFactory;
use App\Shared\Infrastructure\Bus\Event\Async\ResilientAsyncEventDispatcher;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Shared\Infrastructure\Observability\Factory\SqsDispatchFailureMetricFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ResilientAsyncEventDispatcherExecutionTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private SqsDispatchFailureMetricFactory $metricFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->metricFactory = new SqsDispatchFailureMetricFactory(
            new MetricDimensionsFactory()
        );
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
            new DomainEventEnvelopeFactory(),
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
        $messageBus = $this->createSuccessfulMessageBus();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
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
        $messageBus = $this->createFailingMessageBus();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
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
        $messageBus = $this->createFailingMessageBus();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');

        $dispatcher->dispatch($event);
        self::assertTrue(true);
    }

    public function testEmitsMetricOnDispatchFailure(): void
    {
        $messageBus = $this->createFailingMessageBus();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
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
            new DomainEventEnvelopeFactory(),
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
        $messageBus = $this->createMessageBusThatFailsOnSecondCall();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
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
        $messageBus = $this->createFailingMessageBus();

        $this->metricsEmitter->failOnNextCall();

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
            new NullLogger(),
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');

        $result = $dispatcher->dispatch($event);
        self::assertFalse($result);
    }

    private function createFailingMessageBus(): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));
        return $messageBus;
    }

    private function createMessageBusThatFailsOnSecondCall(): MessageBusInterface
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
        return $messageBus;
    }

    private function createSuccessfulMessageBus(): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static fn ($message) => new Envelope($message));
        return $messageBus;
    }
}
