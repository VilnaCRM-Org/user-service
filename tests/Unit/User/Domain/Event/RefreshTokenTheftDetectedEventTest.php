<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;

final class RefreshTokenTheftDetectedEventTest extends UnitTestCase
{
    public function testEventProperties(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $reason = 'grace_period_expired';
        $eventId = $this->faker->uuid();

        $event = new RefreshTokenTheftDetectedEvent(
            $sessionId,
            $userId,
            $ipAddress,
            $reason,
            $eventId
        );

        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($ipAddress, $event->ipAddress);
        $this->assertSame($reason, $event->reason);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $this->assertSame(
            'user.refresh_token_theft_detected',
            RefreshTokenTheftDetectedEvent::eventName()
        );
    }

    public function testToPrimitives(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();

        $event = new RefreshTokenTheftDetectedEvent(
            $sessionId,
            $userId,
            $ipAddress,
            'double_grace_use',
            $this->faker->uuid()
        );

        $this->assertSame(
            [
                'sessionId' => $sessionId,
                'userId' => $userId,
                'ipAddress' => $ipAddress,
                'reason' => 'double_grace_use',
            ],
            $event->toPrimitives()
        );
    }

    public function testFromPrimitives(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = RefreshTokenTheftDetectedEvent::fromPrimitives(
            [
                'sessionId' => $sessionId,
                'userId' => $userId,
                'ipAddress' => '127.0.0.1',
                'reason' => 'grace_period_expired',
            ],
            $eventId,
            '2026-02-11T12:00:00+00:00'
        );

        $this->assertInstanceOf(
            RefreshTokenTheftDetectedEvent::class,
            $event
        );
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($userId, $event->userId);
        $this->assertSame('127.0.0.1', $event->ipAddress);
        $this->assertSame('grace_period_expired', $event->reason);
    }
}
