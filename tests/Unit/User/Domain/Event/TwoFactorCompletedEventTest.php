<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\TwoFactorCompletedEvent;

final class TwoFactorCompletedEventTest extends UnitTestCase
{
    public function testToPrimitivesAndEventName(): void
    {
        $event = new TwoFactorCompletedEvent(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            'totp',
            $this->faker->uuid()
        );

        $primitives = $event->toPrimitives();

        $this->assertSame('user.two_factor_completed', TwoFactorCompletedEvent::eventName());
        $this->assertSame($event->userId, $primitives['userId']);
        $this->assertSame($event->sessionId, $primitives['sessionId']);
        $this->assertSame($event->ipAddress, $primitives['ipAddress']);
        $this->assertSame($event->userAgent, $primitives['userAgent']);
        $this->assertSame($event->method, $primitives['method']);
    }

    public function testFromPrimitives(): void
    {
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-10T22:05:00+00:00';

        $event = TwoFactorCompletedEvent::fromPrimitives(
            [
                'userId' => $this->faker->uuid(),
                'sessionId' => $this->faker->uuid(),
                'ipAddress' => $this->faker->ipv4(),
                'userAgent' => $this->faker->userAgent(),
                'method' => 'totp',
            ],
            $eventId,
            $occurredOn
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
