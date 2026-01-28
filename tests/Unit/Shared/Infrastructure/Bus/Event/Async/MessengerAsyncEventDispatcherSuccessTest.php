<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\MessengerAsyncEventDispatcher;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerAsyncEventDispatcherSuccessTest extends UnitTestCase
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
        $expectedContext = [
            'event_id' => $event->eventId(), 'event_type' => TestEvent::class,
            'event_name' => TestEvent::eventName(),
        ];
        $this->messageBus->expects($this->once())->method('dispatch');
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Domain event dispatched to queue',
                $this->callback(
                    static function (array $context) use ($expectedContext): bool {
                        return $expectedContext === array_intersect_key($context, $expectedContext);
                    }
                )
            );
        self::assertTrue($this->dispatcher->dispatch($event));
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

    public function testDispatchLogsCorrectEventMetadata(): void
    {
        $event = new TestEvent($this->faker->uuid());
        $this->messageBus->method('dispatch');
        $this->logger
            ->expects($this->once())->method('debug')
            ->with(
                'Domain event dispatched to queue',
                $this->callback(
                    static function (array $context) use ($event): bool {
                        return $context['event_id'] === $event->eventId()
                            && $context['event_type'] === TestEvent::class
                            && $context['event_name'] === TestEvent::eventName();
                    }
                )
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
