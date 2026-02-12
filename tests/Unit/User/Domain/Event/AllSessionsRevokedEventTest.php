<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\AllSessionsRevokedEvent;

final class AllSessionsRevokedEventTest extends UnitTestCase
{
    public function testEventProperties(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'two_factor_enabled';
        $revokedCount = 3;
        $eventId = $this->faker->uuid();

        $event = new AllSessionsRevokedEvent(
            $userId,
            $reason,
            $revokedCount,
            $eventId
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($reason, $event->reason);
        $this->assertSame($revokedCount, $event->revokedCount);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $this->assertSame(
            'user.all_sessions_revoked',
            AllSessionsRevokedEvent::eventName()
        );
    }

    public function testToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'two_factor_enabled';

        $event = new AllSessionsRevokedEvent(
            $userId,
            $reason,
            5,
            $this->faker->uuid()
        );

        $this->assertSame(
            [
                'userId' => $userId,
                'reason' => $reason,
                'revokedCount' => 5,
            ],
            $event->toPrimitives()
        );
    }

    public function testFromPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $reason = 'two_factor_enabled';
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-11T12:00:00+00:00';

        $event = AllSessionsRevokedEvent::fromPrimitives(
            [
                'userId' => $userId,
                'reason' => $reason,
                'revokedCount' => '3',
            ],
            $eventId,
            $occurredOn
        );

        $this->assertInstanceOf(
            AllSessionsRevokedEvent::class,
            $event
        );
        $this->assertSame($userId, $event->userId);
        $this->assertSame($reason, $event->reason);
        $this->assertSame(3, $event->revokedCount);
        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
