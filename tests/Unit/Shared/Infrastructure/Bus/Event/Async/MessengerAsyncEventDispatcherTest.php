<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\Async\MessengerAsyncEventDispatcher;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerAsyncEventDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockObject $messageBus;
    private LoggerInterface&MockObject $logger;
    private MessengerAsyncEventDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = new MessengerAsyncEventDispatcher(
            $this->messageBus,
            $this->logger
        );
    }

    public function testDispatchSingleEventSuccess(): void
    {
        $event = new TestEvent($this->faker->uuid());

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch');

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Domain event dispatched to queue',
                $this->callback(
                    fn ($context) =>
                    isset($context['event_id']) &&
                    isset($context['event_type']) &&
                    isset($context['event_name']) &&
                    $context['event_type'] === TestEvent::class &&
                    $context['event_name'] === 'test.event'
                )
            );

        $result = $this->dispatcher->dispatch($event);

        self::assertTrue($result);
    }

    public function testDispatchMultipleEventsSuccess(): void
    {
        $event1 = new TestEvent($this->faker->uuid());
        $event2 = new TestEvent($this->faker->uuid());

        $this->messageBus
            ->expects($this->exactly(2))
            ->method('dispatch');

        $this->logger
            ->expects($this->exactly(2))
            ->method('debug');

        $result = $this->dispatcher->dispatch($event1, $event2);

        self::assertTrue($result);
    }

    public function testDispatchHandlesExceptionAndReturnsFalse(): void
    {
        $event = new TestEvent($this->faker->uuid());
        $exception = new TestMessengerException('Queue unavailable');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to dispatch domain event to queue (Layer 1)',
                $this->callback(
                    fn ($context) =>
                    isset($context['event_id']) &&
                    isset($context['event_type']) &&
                    isset($context['event_name']) &&
                    isset($context['error']) &&
                    isset($context['exception_class']) &&
                    $context['error'] === 'Queue unavailable' &&
                    $context['event_type'] === TestEvent::class
                )
            );

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $result = $this->dispatcher->dispatch($event);

        self::assertFalse($result);
    }

    public function testDispatchPartialFailureReturnsFalse(): void
    {
        $event1 = new TestEvent($this->faker->uuid());
        $event2 = new TestEvent($this->faker->uuid());
        $exception = new TestMessengerException('Queue full');
        $envelope = $this->createMock(Envelope::class);

        $this->messageBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function () use ($exception, $envelope) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    return $envelope; // Success on first call
                }
                throw $exception; // Fail on second call
            });

        $this->logger->expects($this->once())->method('debug');
        $this->logger->expects($this->once())->method('error');

        $result = $this->dispatcher->dispatch($event1, $event2);

        self::assertFalse($result);
    }

    public function testDispatchLogsCorrectEventMetadata(): void
    {
        $event = new TestEvent($this->faker->uuid());

        $this->messageBus->method('dispatch');

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Domain event dispatched to queue',
                $this->callback(function ($context) use ($event) {
                    return $context['event_id'] === $event->eventId() &&
                        $context['event_type'] === TestEvent::class &&
                        $context['event_name'] === 'test.event';
                })
            );

        $this->dispatcher->dispatch($event);
    }

    public function testDispatchEmptyEventsReturnsTrue(): void
    {
        $this->messageBus->expects($this->never())->method('dispatch');
        $this->logger->expects($this->never())->method('debug');
        $this->logger->expects($this->never())->method('error');

        $result = $this->dispatcher->dispatch();

        self::assertTrue($result);
    }
}

final class TestEvent extends DomainEvent
{
    public function __construct(string $eventId, ?string $occurredOn = null)
    {
        parent::__construct($eventId, $occurredOn);
    }

    #[\Override]
    public static function eventName(): string
    {
        return 'test.event';
    }

    #[\Override]
    public function toPrimitives(): array
    {
        return [];
    }

    #[\Override]
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($eventId, $occurredOn);
    }
}

final class TestMessengerException extends \RuntimeException implements ExceptionInterface
{
}
