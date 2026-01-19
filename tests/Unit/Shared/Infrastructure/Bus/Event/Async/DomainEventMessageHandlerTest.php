<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
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
        $event = new TestDomainEvent();
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber = new TestDomainEventSubscriber([TestDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger->expects($this->exactly(2))->method('debug');

        $this->handler->__invoke($envelope);

        self::assertSame(1, $subscriber->callCount);
        self::assertInstanceOf(TestDomainEvent::class, $subscriber->lastEvent);
        self::assertSame($event->eventId(), $subscriber->lastEvent->eventId());
    }

    public function testInvokeSkipsNonMatchingSubscriber(): void
    {
        $event = new TestDomainEvent();
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber = new TestDomainEventSubscriber(['SomeOtherEvent']);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger->expects($this->once())->method('debug')
            ->with('Processing domain event from queue', $this->anything());

        $this->handler->__invoke($envelope);

        self::assertSame(0, $subscriber->callCount);
    }

    public function testInvokeLogsSuccessfulExecution(): void
    {
        $event = new TestDomainEvent();
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber = new TestDomainEventSubscriber([TestDomainEvent::class]);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber],
            $this->logger
        );

        $this->logger->expects($this->exactly(2))->method('debug');

        $this->handler->__invoke($envelope);
    }

    public function testInvokeHandlesSubscriberException(): void
    {
        $event = new TestDomainEvent();
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
                    fn ($context) =>
                    isset($context['subscriber']) &&
                    isset($context['event_id']) &&
                    isset($context['event_type']) &&
                    isset($context['event_name']) &&
                    isset($context['error']) &&
                    isset($context['exception_class']) &&
                    $context['error'] === 'Subscriber failed'
                )
            );

        $this->handler->__invoke($envelope);

        self::assertSame(1, $subscriber->callCount);
    }

    public function testInvokeProcessesMultipleSubscribers(): void
    {
        $event = new TestDomainEvent();
        $envelope = DomainEventEnvelope::fromEvent($event);

        $subscriber1 = new TestDomainEventSubscriber([TestDomainEvent::class]);
        $subscriber2 = new TestDomainEventSubscriber([TestDomainEvent::class]);
        $subscriber3 = new TestDomainEventSubscriber(['SomeOtherEvent']);

        $this->handler = new DomainEventMessageHandler(
            [$subscriber1, $subscriber2, $subscriber3],
            $this->logger
        );

        $this->handler->__invoke($envelope);

        self::assertSame(1, $subscriber1->callCount);
        self::assertSame(1, $subscriber2->callCount);
        self::assertSame(0, $subscriber3->callCount);
    }
}

final class TestDomainEvent extends DomainEvent
{
    public function __construct(?string $eventId = null, ?string $occurredOn = null)
    {
        parent::__construct($eventId ?? 'test-event-id-' . uniqid(), $occurredOn);
    }

    public static function eventName(): string
    {
        return 'test.event';
    }

    public function toPrimitives(): array
    {
        return ['data' => 'test'];
    }

    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($eventId, $occurredOn);
    }
}

final class TestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    public int $callCount = 0;
    public ?DomainEvent $lastEvent = null;

    /**
     * @param array<string> $subscribedEvents
     */
    public function __construct(
        private array $subscribedEvents,
        private ?\Throwable $exceptionToThrow = null
    ) {
    }

    public function subscribedTo(): array
    {
        return $this->subscribedEvents;
    }

    public function __invoke(DomainEvent $event): void
    {
        $this->callCount++;
        $this->lastEvent = $event;

        if ($this->exceptionToThrow !== null) {
            throw $this->exceptionToThrow;
        }
    }
}
