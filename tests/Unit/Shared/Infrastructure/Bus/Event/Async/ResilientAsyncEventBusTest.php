<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Shared\Application\Bus\Event\AsyncEventDispatcherInterface;
use App\Shared\Infrastructure\Bus\Event\Async\ResilientAsyncEventBus;
use App\Tests\Unit\UnitTestCase;

final class ResilientAsyncEventBusTest extends UnitTestCase
{
    public function testPublishDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(AsyncEventDispatcherInterface::class);
        $event = new ResilientAsyncEventBusTestEvent($this->faker->uuid());

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn(true);

        $bus = new ResilientAsyncEventBus($dispatcher);

        $bus->publish($event);
    }
}
