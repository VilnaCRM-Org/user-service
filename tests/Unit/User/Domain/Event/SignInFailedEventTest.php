<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\SignInFailedEvent;

final class SignInFailedEventTest extends UnitTestCase
{
    public function testToPrimitivesAndEventName(): void
    {
        $event = new SignInFailedEvent(
            $this->faker->email(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            'invalid_credentials',
            $this->faker->uuid()
        );

        $primitives = $event->toPrimitives();

        $this->assertSame('user.sign_in_failed', SignInFailedEvent::eventName());
        $this->assertSame($event->email, $primitives['email']);
        $this->assertSame($event->ipAddress, $primitives['ipAddress']);
        $this->assertSame($event->userAgent, $primitives['userAgent']);
        $this->assertSame($event->reason, $primitives['reason']);
    }

    public function testFromPrimitives(): void
    {
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-10T22:06:00+00:00';

        $event = SignInFailedEvent::fromPrimitives(
            [
                'email' => $this->faker->email(),
                'ipAddress' => $this->faker->ipv4(),
                'userAgent' => $this->faker->userAgent(),
                'reason' => 'invalid_credentials',
            ],
            $eventId,
            $occurredOn
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
