<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Infrastructure\Bus\Event\Async\ResilientAsyncEventBus;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\AsyncEventDispatcherSpy;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub\TestDomainEvent;
use App\Tests\Unit\UnitTestCase;

final class ResilientAsyncEventBusTest extends UnitTestCase
{
    public function testPublishesEventsToDispatcher(): void
    {
        $dispatcher = new AsyncEventDispatcherSpy();
        $eventBus = new ResilientAsyncEventBus($dispatcher);

        $event = new TestDomainEvent('aggregate-123', 'event-456');
        $eventBus->publish($event);

        self::assertCount(1, $dispatcher->dispatched());
        self::assertSame($event, $dispatcher->dispatched()[0]);
    }

    public function testPublishesMultipleEvents(): void
    {
        $dispatcher = new AsyncEventDispatcherSpy();
        $eventBus = new ResilientAsyncEventBus($dispatcher);

        $event1 = new TestDomainEvent('aggregate-1', 'event-1');
        $event2 = new TestDomainEvent('aggregate-2', 'event-2');
        $eventBus->publish($event1, $event2);

        self::assertCount(2, $dispatcher->dispatched());
    }

    public function testNeverThrowsEvenOnDispatchFailure(): void
    {
        $dispatcher = new AsyncEventDispatcherSpy();
        $dispatcher->failNextDispatch();
        $eventBus = new ResilientAsyncEventBus($dispatcher);

        $event = new TestDomainEvent('aggregate-123', 'event-456');

        // Should not throw - AP from CAP theorem
        $eventBus->publish($event);
        self::assertTrue(true);
    }

    public function testImplementsEventBusInterface(): void
    {
        $dispatcher = new AsyncEventDispatcherSpy();
        $eventBus = new ResilientAsyncEventBus($dispatcher);

        self::assertInstanceOf(\App\Shared\Domain\Bus\Event\EventBusInterface::class, $eventBus);
    }
}
