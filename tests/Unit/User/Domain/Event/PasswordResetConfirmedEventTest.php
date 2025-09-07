<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetConfirmedEvent;

final class PasswordResetConfirmedEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userId = '123e4567-e89b-12d3-a456-426614174000';
        $eventId = 'event123';
        $event = new PasswordResetConfirmedEvent($userId, $eventId);

        $this->assertSame($userId, $event->userId);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $eventName = PasswordResetConfirmedEvent::eventName();

        $this->assertSame('user.password_reset_confirmed', $eventName);
    }

    public function testToPrimitives(): void
    {
        $userId = '123e4567-e89b-12d3-a456-426614174000';
        $eventId = 'event123';
        $event = new PasswordResetConfirmedEvent($userId, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertSame($userId, $primitives['userId']);
    }

    public function testFromPrimitives(): void
    {
        $userId = '123e4567-e89b-12d3-a456-426614174000';
        $body = [
            'userId' => $userId,
        ];
        $eventId = 'event123';
        $occurredOn = '2023-01-01 12:00:00';

        $event = PasswordResetConfirmedEvent::fromPrimitives($body, $eventId, $occurredOn);

        $this->assertInstanceOf(PasswordResetConfirmedEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($eventId, $event->eventId());
    }
}
