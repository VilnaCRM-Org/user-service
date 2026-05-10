<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\AccountLockedOutEvent;

final class AccountLockedOutEventTest extends UnitTestCase
{
    public function testToPrimitivesAndEventName(): void
    {
        $event = new AccountLockedOutEvent(
            $this->faker->email(),
            $this->faker->numberBetween(1, 20),
            $this->faker->numberBetween(300, 3600),
            $this->faker->uuid()
        );

        $primitives = $event->toPrimitives();

        $this->assertSame('user.account_locked_out', AccountLockedOutEvent::eventName());
        $this->assertSame($event->email, $primitives['email']);
        $this->assertSame($event->failedAttempts, $primitives['failedAttempts']);
        $this->assertSame($event->lockoutDurationSeconds, $primitives['lockoutDurationSeconds']);
    }

    public function testFromPrimitives(): void
    {
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-10T22:07:00+00:00';

        $event = AccountLockedOutEvent::fromPrimitives(
            [
                'email' => $this->faker->email(),
                'failedAttempts' => (string) $this->faker->numberBetween(1, 20),
                'lockoutDurationSeconds' => (string) $this->faker->numberBetween(300, 3600),
            ],
            $eventId,
            $occurredOn
        );

        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
