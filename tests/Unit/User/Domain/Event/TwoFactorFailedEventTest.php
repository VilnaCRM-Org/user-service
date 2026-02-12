<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\TwoFactorFailedEvent;

final class TwoFactorFailedEventTest extends UnitTestCase
{
    public function testToPrimitivesAndEventName(): void
    {
        $event = new TwoFactorFailedEvent(
            $this->faker->uuid(),
            $this->faker->ipv4(),
            'invalid_code',
            $this->faker->uuid()
        );

        $primitives = $event->toPrimitives();

        $this->assertSame('user.two_factor_failed', TwoFactorFailedEvent::eventName());
        $this->assertSame($event->pendingSessionId, $primitives['pendingSessionId']);
        $this->assertSame($event->ipAddress, $primitives['ipAddress']);
        $this->assertSame($event->reason, $primitives['reason']);
    }

    public function testFromPrimitives(): void
    {
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-10T22:05:00+00:00';

        $event = TwoFactorFailedEvent::fromPrimitives(
            [
                'pendingSessionId' => $this->faker->uuid(),
                'ipAddress' => $this->faker->ipv4(),
                'reason' => 'invalid_code',
            ],
            $eventId,
            $occurredOn
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
