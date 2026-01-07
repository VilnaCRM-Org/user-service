<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\PartlyCoveredEventBus;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;

/**
 * @covers \App\Shared\Infrastructure\Bus\Event\PartlyCoveredEventBus
 */
final class PartlyCoveredEventBusTest extends UnitTestCase
{
    private MessageBus $messageBus;
    private PartlyCoveredEventBus $eventBus;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBus::class);
        $this->eventBus = new PartlyCoveredEventBus($this->messageBus);
    }

    public function testPublishWithSingleEvent(): void
    {
        $event = $this->createMock(DomainEvent::class);

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $this->eventBus->publish($event);
    }

    public function testPublishWithMultipleEvents(): void
    {
        $event1 = $this->createMock(DomainEvent::class);
        $event2 = $this->createMock(DomainEvent::class);

        $this->messageBus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$event1], [$event2]],
                    static fn (DomainEvent $event) => new Envelope($event)
                )
            );

        $this->eventBus->publish($event1, $event2);
    }

    public function testPublishWithNoEvents(): void
    {
        $this->messageBus->expects($this->never())
            ->method('dispatch');

        $this->eventBus->publish();
    }

    public function testGetEventCountWithEmptyArray(): void
    {
        $count = $this->eventBus->getEventCount([]);

        $this->assertSame(0, $count);
    }

    public function testGetEventCountWithDomainEvents(): void
    {
        $event1 = $this->createMock(DomainEvent::class);
        $event2 = $this->createMock(DomainEvent::class);

        $count = $this->eventBus->getEventCount([$event1, $event2]);

        $this->assertSame(2, $count);
    }

    public function testGetEventCountWithMixedEvents(): void
    {
        $domainEvent = $this->createMock(DomainEvent::class);
        $nonDomainEvent = new \stdClass();

        $count = $this->eventBus->getEventCount([$domainEvent, $nonDomainEvent]);

        $this->assertSame(1, $count);
    }

    public function testGetEventCountWithNonDomainEvents(): void
    {
        $nonDomainEvent1 = new \stdClass();
        $nonDomainEvent2 = new \ArrayObject();

        $count = $this->eventBus->getEventCount([$nonDomainEvent1, $nonDomainEvent2]);

        $this->assertSame(0, $count);
    }

    public function testGetEventCountWithSingleDomainEvent(): void
    {
        $event = $this->createMock(DomainEvent::class);

        $count = $this->eventBus->getEventCount([$event]);

        $this->assertSame(1, $count);
    }
}
