<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\EventNotRegisteredException;
use App\Shared\Infrastructure\Bus\Event\InMemorySymfonyEventBus;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\UnitTestCase;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;

final class InMemorySymfonyEventBusTest extends UnitTestCase
{
    private MessageBusFactory $messageBusFactory;

    /**
     * @var array<DomainEvent>
     */
    private array $eventSubscribers;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBusFactory =
            $this->createMock(MessageBusFactory::class);
        $this->eventSubscribers =
            [$this->createMock(DomainEvent::class)];
    }

    public function testPublishSucceeds(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($event);
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $eventBus = new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers
        );

        $eventBus->publish($event);
    }

    public function testDispatchWithNoHandlerForMessageException(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new NoHandlerForMessageException());
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $eventBus = new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers
        );

        $this->expectException(EventNotRegisteredException::class);

        $eventBus->publish($event);
    }

    public function testDispatchWithHandlerFailedException(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(
                $this->createMock(HandlerFailedException::class)
            );
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $eventBus = new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers
        );

        $this->expectException(HandlerFailedException::class);

        $eventBus->publish($event);
    }

    public function testDispatchUnwrapsHandlerFailedPreviousException(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $previous = new \LogicException('previous event failure');
        $handlerFailed = new HandlerFailedException(
            new Envelope(new \stdClass()),
            [$previous]
        );

        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException($handlerFailed);
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $eventBus = new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers
        );

        $this->expectExceptionObject($previous);

        $eventBus->publish($event);
    }

    public function testEventBusHelperMethodsAreCovered(): void
    {
        $messageBus = $this->createMock(MessageBus::class);
        $this->messageBusFactory->method('create')->willReturn($messageBus);
        $eventBus = new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers
        );

        $reflection = new ReflectionClass($eventBus);

        $event = $this->createMock(DomainEvent::class);
        $eventNotRegistered = $reflection->getMethod('eventNotRegistered');
        $this->makeAccessible($eventNotRegistered);

        $exception = $eventNotRegistered->invoke($eventBus, $event);
        self::assertInstanceOf(EventNotRegisteredException::class, $exception);

        $unwrap = $reflection->getMethod('unwrapHandlerFailure');
        $this->makeAccessible($unwrap);
        $handlerFailure = new HandlerFailedException(
            new Envelope(new \stdClass()),
            [new \RuntimeException('event failure')]
        );

        $result = $unwrap->invoke($eventBus, $handlerFailure);
        self::assertInstanceOf(\RuntimeException::class, $result);

        $handle = $reflection->getMethod('handleDispatchException');
        $this->makeAccessible($handle);
        $otherException = new \RuntimeException('other event failure');

        try {
            $handle->invoke($eventBus, $otherException, $event);
            $this->fail('Expected exception not thrown.');
        } catch (\RuntimeException $caught) {
            self::assertSame($otherException, $caught);
        }
    }

    public function testDispatchWithThrowable(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException());
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);
        $eventBus = new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers
        );

        $this->expectException(\RuntimeException::class);

        $eventBus->publish($event);
    }
}
