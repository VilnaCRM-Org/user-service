<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\SessionRevokedEvent;

final class SessionRevokedEventTest extends UnitTestCase
{
    public function testToPrimitivesAndEventName(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $reason = 'logout';
        $eventId = $this->faker->uuid();

        $event = new SessionRevokedEvent($userId, $sessionId, $reason, $eventId);

        $primitives = $event->toPrimitives();

        $this->assertSame('user.session.revoked', SessionRevokedEvent::eventName());
        $this->assertSame($userId, $primitives['userId']);
        $this->assertSame($sessionId, $primitives['sessionId']);
        $this->assertSame($reason, $primitives['reason']);
    }

    public function testFromPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $reason = 'password_change';
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-12T22:00:00+00:00';

        $event = SessionRevokedEvent::fromPrimitives(
            [
                'userId' => $userId,
                'sessionId' => $sessionId,
                'reason' => $reason,
            ],
            $eventId,
            $occurredOn
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
        $this->assertSame($userId, $event->userId);
        $this->assertSame($sessionId, $event->sessionId);
        $this->assertSame($reason, $event->reason);
    }
}
