<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelope;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventMessageHandler;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class DomainEventMessageHandlerTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private DomainEventMessageHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testInvokeDispatchesEventToMatchingSubscriber(): void
    {
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber = new TestDomainEventSubscriber([TestDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger->expects($this->exactly(2))->method('debug');

        $this->handler->__invoke($envelope);

        self::assertSame(1, $subscriber->getCallCount());
        self::assertInstanceOf(TestDomainEvent::class, $subscriber->getLastEvent());
        self::assertSame($event->eventId(), $subscriber->getLastEvent()?->eventId());
    }

    public function testInvokeSkipsNonMatchingSubscriber(): void
    {
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber = new TestDomainEventSubscriber([TestOtherDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger->expects($this->once())->method('debug')
            ->with('Processing domain event from queue', $this->anything());

        $this->handler->__invoke($envelope);

        self::assertSame(0, $subscriber->getCallCount());
    }

    public function testInvokeLogsSuccessfulExecution(): void
    {
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber = new TestDomainEventSubscriber([TestDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->willReturnCallback(
                $this->expectSequential(
                    $this->expectedDebugCalls($event)
                )
            );

        $this->handler->__invoke($envelope);
    }

    public function testInvokeContinuesAfterNonMatchingSubscriber(): void
    {
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);

        $nonMatchingSubscriber = new TestDomainEventSubscriber([TestOtherDomainEvent::class]);
        $matchingSubscriber = new TestDomainEventSubscriber([TestDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$nonMatchingSubscriber, $matchingSubscriber],
            $this->logger
        );

        $this->handler->__invoke($envelope);

        self::assertSame(0, $nonMatchingSubscriber->getCallCount());
        self::assertSame(1, $matchingSubscriber->getCallCount());
    }

    public function testInvokeHandlesSubscriberException(): void
    {
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);
        $exception = new \RuntimeException('Subscriber failed');

        $subscriber = new TestDomainEventSubscriber([TestDomainEvent::class], $exception);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger->expects($this->once())->method('error')
            ->with(
                'Domain event subscriber execution failed in worker',
                $this->callback(
                    fn (array $context) => $this->hasErrorContext(
                        $context,
                        'Subscriber failed'
                    )
                )
            );

        $this->handler->__invoke($envelope);

        self::assertSame(1, $subscriber->getCallCount());
    }

    public function testInvokeProcessesMultipleSubscribers(): void
    {
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber1 = new TestDomainEventSubscriber([TestDomainEvent::class]);
        $subscriber2 = new TestDomainEventSubscriber([TestDomainEvent::class]);
        $subscriber3 = new TestDomainEventSubscriber([TestOtherDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber1, $subscriber2, $subscriber3],
            $this->logger
        );

        $this->handler->__invoke($envelope);

        self::assertSame(1, $subscriber1->getCallCount());
        self::assertSame(1, $subscriber2->getCallCount());
        self::assertSame(0, $subscriber3->getCallCount());
    }

    public function testInvokeLogsSubscriberContext(): void
    {
        $logger = new RecordingLogger();
        $event = new TestDomainEvent($this->faker->uuid());
        $envelope = DomainEventEnvelope::fromEvent($event);
        $subscriber = new TestDomainEventSubscriber([TestDomainEvent::class]);

        $handler = new DomainEventMessageHandler([$subscriber], $logger);
        $handler->__invoke($envelope);

        $successContext = null;

        foreach ($logger->getDebugCalls() as [$message, $context]) {
            if ($message === 'Subscriber executed successfully') {
                $successContext = $context;
                break;
            }
        }

        $this->assertNotNull($successContext);
        $this->assertSame([
            'subscriber' => TestDomainEventSubscriber::class,
            'event_id' => $event->eventId(),
        ], $successContext);
    }

    /**
     * @param array<string, string> $context
     */
    private function hasErrorContext(array $context, string $message): bool
    {
        $requiredKeys = [
            'subscriber',
            'event_id',
            'event_type',
            'event_name',
            'error',
            'exception_class',
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $context)) {
                return false;
            }
        }

        return $context['error'] === $message;
    }

    /**
     * @return array<int, array<int, array<string, string>|string>>
     */
    private function expectedDebugCalls(TestDomainEvent $event): array
    {
        return [
            [
                'Processing domain event from queue',
                [
                    'event_id' => $event->eventId(),
                    'event_type' => TestDomainEvent::class,
                    'event_name' => TestDomainEvent::eventName(),
                ],
            ],
            [
                'Subscriber executed successfully',
                [
                    'subscriber' => TestDomainEventSubscriber::class,
                    'event_id' => $event->eventId(),
                ],
            ],
        ];
    }
}
