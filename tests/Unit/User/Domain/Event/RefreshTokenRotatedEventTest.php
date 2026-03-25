<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\RefreshTokenRotatedEvent;

final class RefreshTokenRotatedEventTest extends UnitTestCase
{
    public function testEventProperties(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = new RefreshTokenRotatedEvent(
            $sessionId,
            $userId,
            $eventId
        );

        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $this->assertSame(
            'user.refresh_token_rotated',
            RefreshTokenRotatedEvent::eventName()
        );
    }

    public function testToPrimitives(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $event = new RefreshTokenRotatedEvent(
            $sessionId,
            $userId,
            $this->faker->uuid()
        );

        $this->assertSame(
            ['sessionId' => $sessionId, 'userId' => $userId],
            $event->toPrimitives()
        );
    }

    public function testFromPrimitives(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = RefreshTokenRotatedEvent::fromPrimitives(
            ['sessionId' => $sessionId, 'userId' => $userId],
            $eventId,
            '2026-02-11T12:00:00+00:00'
        );

        $this->assertInstanceOf(
            RefreshTokenRotatedEvent::class,
            $event
        );
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($userId, $event->userId);
    }
}
