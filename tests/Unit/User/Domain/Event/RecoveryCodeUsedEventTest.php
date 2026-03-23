<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\RecoveryCodeUsedEvent;

final class RecoveryCodeUsedEventTest extends UnitTestCase
{
    public function testEventProperties(): void
    {
        $userId = $this->faker->uuid();
        $remainingCount = 5;
        $eventId = $this->faker->uuid();

        $event = new RecoveryCodeUsedEvent(
            $userId,
            $remainingCount,
            $eventId
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($remainingCount, $event->remainingCount);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $this->assertSame(
            'user.recovery_code_used',
            RecoveryCodeUsedEvent::eventName()
        );
    }

    public function testToPrimitives(): void
    {
        $userId = $this->faker->uuid();

        $event = new RecoveryCodeUsedEvent(
            $userId,
            3,
            $this->faker->uuid()
        );

        $this->assertSame(
            ['userId' => $userId, 'remainingCount' => 3],
            $event->toPrimitives()
        );
    }

    public function testFromPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-11T12:00:00+00:00';

        $event = RecoveryCodeUsedEvent::fromPrimitives(
            ['userId' => $userId, 'remainingCount' => '2'],
            $eventId,
            $occurredOn
        );

        $this->assertInstanceOf(
            RecoveryCodeUsedEvent::class,
            $event
        );
        $this->assertSame($userId, $event->userId);
        $this->assertSame(2, $event->remainingCount);
    }
}
