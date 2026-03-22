<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelope;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventMessageHandler;
use App\Shared\Infrastructure\Observability\Factory\EventSubscriberFailureMetricFactory;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;

final class DomainEventMessageHandlerLoggingTest extends UnitTestCase
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

    public function testLogsDebugMessageWithEventContext(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $capturedContext = [];

        $logger = $this->createDebugLoggerForMessage(
            'Processing domain event from queue',
            $capturedContext
        );
        $event = new TestDomainEvent('aggregate-123', 'event-456');

        $handler = $this->createHandlerWithLogger([$subscriber], $logger);
        $handler(DomainEventEnvelope::fromEvent($event));

        self::assertSame($event->eventId(), $capturedContext['event_id']);
        self::assertSame(TestDomainEvent::class, $capturedContext['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $capturedContext['event_name']);
    }

    public function testLogsSubscriberExecutionWithContext(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $capturedContext = [];

        $logger = $this->createDebugLoggerForMessage(
            'Subscriber executed successfully',
            $capturedContext
        );
        $event = new TestDomainEvent('aggregate-123', 'event-456');

        $handler = $this->createHandlerWithLogger([$subscriber], $logger);
        $handler(DomainEventEnvelope::fromEvent($event));

        self::assertSame(TestDomainEventSubscriber::class, $capturedContext['subscriber']);
        self::assertSame($event->eventId(), $capturedContext['event_id']);
    }

    public function testLogsErrorWithFullContextOnSubscriberFailure(): void
    {
        $subscriber = new TestDomainEventSubscriber();
        $subscriber->failOnNextCall();
        $capturedContext = [];

        $logger = $this->createErrorLoggerForMessage(
            'Domain event subscriber execution failed in worker',
            $capturedContext
        );
        $event = new TestDomainEvent('aggregate-123', 'event-456');

        $handler = $this->createHandlerWithLogger([$subscriber], $logger);
        $handler(DomainEventEnvelope::fromEvent($event));

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

        $logger = $this->createWarningLoggerForMessage(
            'Failed to emit subscriber failure metric',
            $capturedContext
        );

        $handler = $this->createHandlerWithLogger([$subscriber], $logger);
        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $handler(DomainEventEnvelope::fromEvent($event));

        self::assertArrayHasKey('error', $capturedContext);
        self::assertSame('Metric emission failed', $capturedContext['error']);
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createDebugLoggerForMessage(
        string $expectedMessage,
        array &$capturedContext
    ): \Psr\Log\LoggerInterface {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('debug')
            ->willReturnCallback(
                static function (string $message, array $context) use (
                    $expectedMessage,
                    &$capturedContext
                ): void {
                    if ($message === $expectedMessage) {
                        $capturedContext = $context;
                    }
                }
            );
        return $logger;
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createErrorLoggerForMessage(
        string $expectedMessage,
        array &$capturedContext
    ): \Psr\Log\LoggerInterface {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('error')
            ->willReturnCallback(
                static function (string $message, array $context) use (
                    $expectedMessage,
                    &$capturedContext
                ): void {
                    if ($message === $expectedMessage) {
                        $capturedContext = $context;
                    }
                }
            );
        return $logger;
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createWarningLoggerForMessage(
        string $expectedMessage,
        array &$capturedContext
    ): \Psr\Log\LoggerInterface {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('warning')
            ->willReturnCallback(
                static function (string $message, array $context) use (
                    $expectedMessage,
                    &$capturedContext
                ): void {
                    if ($message === $expectedMessage) {
                        $capturedContext = $context;
                    }
                }
            );
        return $logger;
    }

    /**
     * @param array<int, TestDomainEventSubscriber> $subscribers
     */
    private function createHandlerWithLogger(
        array $subscribers,
        \Psr\Log\LoggerInterface $logger
    ): DomainEventMessageHandler {
        return new DomainEventMessageHandler(
            $subscribers,
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );
    }
}
