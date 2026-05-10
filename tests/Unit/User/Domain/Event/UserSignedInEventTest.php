<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserSignedInEvent;

final class UserSignedInEventTest extends UnitTestCase
{
    public function testToPrimitivesAndEventName(): void
    {
        $event = new UserSignedInEvent(
            $this->faker->uuid(),
            $this->faker->email(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $this->faker->uuid()
        );

        $primitives = $event->toPrimitives();

        $this->assertSame('user.signed_in', UserSignedInEvent::eventName());
        $this->assertSame($event->userId, $primitives['userId']);
        $this->assertSame($event->email, $primitives['email']);
        $this->assertSame($event->sessionId, $primitives['sessionId']);
        $this->assertSame($event->ipAddress, $primitives['ipAddress']);
        $this->assertSame($event->userAgent, $primitives['userAgent']);
        $this->assertSame($event->twoFactorUsed, $primitives['twoFactorUsed']);
    }

    public function testFromPrimitives(): void
    {
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-10T22:05:00+00:00';

        $event = UserSignedInEvent::fromPrimitives(
            [
                'userId' => $this->faker->uuid(),
                'email' => $this->faker->email(),
                'sessionId' => $this->faker->uuid(),
                'ipAddress' => $this->faker->ipv4(),
                'userAgent' => $this->faker->userAgent(),
                'twoFactorUsed' => false,
            ],
            $eventId,
            $occurredOn
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
