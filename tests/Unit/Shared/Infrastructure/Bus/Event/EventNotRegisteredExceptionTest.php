<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\EventNotRegisteredException;
use App\Tests\Unit\UnitTestCase;

class EventNotRegisteredExceptionTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $eventClass = get_class($event);

        $exception = new EventNotRegisteredException($event);

        $expectedMessage = "The event <{$eventClass}> hasn't an event handler associated";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }
}
