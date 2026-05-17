<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\Event\EventNotRegisteredException;
use App\Shared\Infrastructure\Bus\Event\InMemorySymfonyEventBus;
use App\Shared\Infrastructure\Bus\Extractor\CallableFirstParameterExtractor;
use App\Shared\Infrastructure\Bus\InvokeParameterExtractor;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Shared\Infrastructure\Observability\Factory\EventSubscriberFailureMetricFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\FailingTestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\RecordingTestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;

final class InMemorySymfonyEventBusTest extends UnitTestCase
{
    private MessageBusFactory $messageBusFactory;

    /**
     * @var array<DomainEventSubscriberInterface>
     */
    private array $eventSubscribers;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBusFactory =
            $this->createMock(MessageBusFactory::class);
        $this->eventSubscribers =
            [$this->createMock(DomainEventSubscriberInterface::class)];
    }

    public function testDispatchWithNoHandlerForMessageException(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new NoHandlerForMessageException());
        $eventBus = $this->createEventBus($messageBus);

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
        $eventBus = $this->createEventBus($messageBus);

        $this->expectException(HandlerFailedException::class);

        $eventBus->publish($event);
    }

    public function testDispatchWithThrowable(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException());
        $eventBus = $this->createEventBus($messageBus);

        $this->expectException(\RuntimeException::class);

        $eventBus->publish($event);
    }

    public function testSubscriberFailureDoesNotStopRemainingSubscribers(): void
    {
        $successSubscriber = new RecordingTestDomainEventSubscriber();
        $metricsEmitter = new BusinessMetricsEmitterSpy();
        $eventBus = new InMemorySymfonyEventBus(
            new MessageBusFactory(
                new CallableFirstParameterExtractor(new InvokeParameterExtractor())
            ),
            [
                new FailingTestDomainEventSubscriber(),
                $successSubscriber,
            ],
            new NullLogger(),
            $metricsEmitter,
            new EventSubscriberFailureMetricFactory()
        );

        $eventBus->publish(new TestDomainEvent(
            $this->faker->uuid(),
            $this->faker->word()
        ));

        self::assertTrue($successSubscriber->wasCalled());
        self::assertSame(1, $metricsEmitter->count());
    }

    private function createEventBus(MessageBus $messageBus): InMemorySymfonyEventBus
    {
        $this->messageBusFactory->method('create')
            ->willReturn($messageBus);

        return new InMemorySymfonyEventBus(
            $this->messageBusFactory,
            $this->eventSubscribers,
            new NullLogger(),
            new BusinessMetricsEmitterSpy(),
            new EventSubscriberFailureMetricFactory()
        );
    }
}
