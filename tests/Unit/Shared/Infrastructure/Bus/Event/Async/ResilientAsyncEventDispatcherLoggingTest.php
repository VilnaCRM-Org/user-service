<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelopeFactory;
use App\Shared\Infrastructure\Bus\Event\Async\ResilientAsyncEventDispatcher;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Shared\Infrastructure\Observability\Factory\SqsDispatchFailureMetricFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ResilientAsyncEventDispatcherLoggingTest extends UnitTestCase
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

    public function testLogsDebugMessageOnSuccessfulDispatch(): void
    {
        $messageBus = $this->createSuccessfulMessageBus();
        $capturedContext = [];
        $logger = $this->createLoggerWithContextCapture($capturedContext, 'debug');

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        $this->assertDebugContextMatchesEvent($capturedContext, $event);
    }

    public function testLogsErrorWithContextOnDispatchFailure(): void
    {
        $messageBus = $this->createFailingMessageBus();
        $capturedContext = [];
        $logger = $this->createLoggerWithContextCapture($capturedContext, 'error');

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        $this->assertErrorContextMatchesEvent($capturedContext, $event);
    }

    public function testLogsWarningWhenMetricEmissionFails(): void
    {
        $messageBus = $this->createFailingMessageBus();
        $this->metricsEmitter->failOnNextCall();

        $capturedContext = [];
        $logger = $this->createLoggerWithContextCapture($capturedContext, 'warning');

        $dispatcher = new ResilientAsyncEventDispatcher(
            $messageBus,
            new DomainEventEnvelopeFactory(),
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $dispatcher->dispatch($event);

        self::assertArrayHasKey('error', $capturedContext);
        self::assertSame('Metric emission failed', $capturedContext['error']);
    }

    private function createFailingMessageBus(): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('SQS unavailable'));
        return $messageBus;
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createLoggerWithContextCapture(
        array &$capturedContext,
        string $level
    ): \Psr\Log\LoggerInterface {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method($level)
            ->willReturnCallback(
                static function (string $message, array $context) use (&$capturedContext): void {
                    $capturedContext = $context;
                }
            );
        return $logger;
    }

    /**
     * @param array<string, string> $context
     */
    private function assertErrorContextMatchesEvent(array $context, TestDomainEvent $event): void
    {
        self::assertSame($event->eventId(), $context['event_id']);
        self::assertSame(TestDomainEvent::class, $context['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $context['event_name']);
        self::assertSame('SQS unavailable', $context['error']);
        self::assertSame(\RuntimeException::class, $context['exception_class']);
    }

    private function createSuccessfulMessageBus(): MessageBusInterface
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(static fn ($message) => new Envelope($message));
        return $messageBus;
    }

    /**
     * @param array<string, string> $context
     */
    private function assertDebugContextMatchesEvent(array $context, TestDomainEvent $event): void
    {
        self::assertSame($event->eventId(), $context['event_id']);
        self::assertSame(TestDomainEvent::class, $context['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $context['event_name']);
    }
}
