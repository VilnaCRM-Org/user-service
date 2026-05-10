<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\Event\DefensiveEventSubscriberDecorator;
use App\Shared\Infrastructure\Observability\Factory\EventSubscriberFailureMetricFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\FailingTestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\RecordingTestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\TestSubscriberFailureException;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DefensiveEventSubscriberDecoratorTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private EventSubscriberFailureMetricFactory $metricFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->metricFactory = new EventSubscriberFailureMetricFactory();
    }

    public function testDelegatesSubscribedEventsToInnerSubscriber(): void
    {
        $subscriber = new RecordingTestDomainEventSubscriber();
        $decorator = $this->createDecorator($subscriber, new NullLogger());

        self::assertSame([TestDomainEvent::class], $decorator->subscribedTo());
    }

    public function testDelegatesEventHandlingToInnerSubscriber(): void
    {
        $subscriber = new RecordingTestDomainEventSubscriber();
        $decorator = $this->createDecorator($subscriber, new NullLogger());
        $event = $this->createTestEvent();

        $decorator($event);

        self::assertSame($event, $subscriber->handledEvent());
    }

    public function testCatchesSubscriberFailureAndEmitsMetric(): void
    {
        $capturedContext = [];
        $logger = $this->createErrorLogger($capturedContext);
        $subscriber = new FailingTestDomainEventSubscriber();
        $decorator = $this->createDecorator($subscriber, $logger);
        $eventId = $this->faker->uuid();
        $event = $this->createTestEvent($eventId);

        $decorator($event);

        $this->assertFailureContext(
            $capturedContext,
            $subscriber::class,
            $eventId,
            TestSubscriberFailureException::class,
            '0',
        );
        self::assertSame(1, $this->metricsEmitter->count());
    }

    public function testCatchesMetricEmissionFailure(): void
    {
        $capturedContext = [];
        $logger = $this->createWarningLogger($capturedContext);
        $subscriber = new FailingTestDomainEventSubscriber();
        $decorator = $this->createDecorator($subscriber, $logger);
        $event = $this->createTestEvent();

        $this->metricsEmitter->failOnNextCall();

        $decorator($event);

        $this->assertFailureContext(
            $capturedContext,
            $subscriber::class,
            $event->eventId(),
            \RuntimeException::class,
            '0',
        );
    }

    private function createDecorator(
        DomainEventSubscriberInterface $subscriber,
        LoggerInterface $logger
    ): DefensiveEventSubscriberDecorator {
        return new DefensiveEventSubscriberDecorator(
            $subscriber,
            $logger,
            $this->metricsEmitter,
            $this->metricFactory
        );
    }

    private function createTestEvent(?string $eventId = null): TestDomainEvent
    {
        return new TestDomainEvent(
            $this->faker->uuid(),
            $this->faker->word(),
            $eventId
        );
    }

    /**
     * @param array<string, string> $capturedContext
     */
    private function assertFailureContext(
        array $capturedContext,
        string $subscriberClass,
        string $eventId,
        string $exceptionClass,
        string $exceptionCode
    ): void {
        self::assertSame($subscriberClass, $capturedContext['subscriber']);
        self::assertSame($eventId, $capturedContext['event_id']);
        self::assertSame(TestDomainEvent::class, $capturedContext['event_type']);
        self::assertSame(TestDomainEvent::eventName(), $capturedContext['event_name']);
        self::assertArrayNotHasKey('error', $capturedContext);
        self::assertSame($exceptionClass, $capturedContext['exception_class']);
        self::assertSame($exceptionCode, $capturedContext['exception_code']);
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createErrorLogger(
        array &$capturedContext
    ): \PHPUnit\Framework\MockObject\MockObject&LoggerInterface {
        return $this->createContextCapturingLogger(
            'error',
            'Domain event subscriber execution failed',
            $capturedContext
        );
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createWarningLogger(
        array &$capturedContext
    ): \PHPUnit\Framework\MockObject\MockObject&LoggerInterface {
        return $this->createContextCapturingLogger(
            'warning',
            'Failed to emit subscriber failure metric',
            $capturedContext
        );
    }

    /**
     * @param array<string, string> &$capturedContext
     */
    private function createContextCapturingLogger(
        string $method,
        string $expectedMessage,
        array &$capturedContext
    ): \PHPUnit\Framework\MockObject\MockObject&LoggerInterface {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method($method)
            ->willReturnCallback(
                static function (
                    string $message,
                    array $context
                ) use (
                    &$capturedContext,
                    $expectedMessage
                ): void {
                    if ($message === $expectedMessage) {
                        $capturedContext = $context;
                    }
                }
            );

        return $logger;
    }
}
