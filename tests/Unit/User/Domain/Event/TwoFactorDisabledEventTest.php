<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\TwoFactorDisabledEvent;

final class TwoFactorDisabledEventTest extends UnitTestCase
{
    public function testEventProperties(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new TwoFactorDisabledEvent(
            $userId,
            $email,
            $eventId
        );

        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $this->assertSame(
            'user.two_factor_disabled',
            TwoFactorDisabledEvent::eventName()
        );
    }

    public function testToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();

        $event = new TwoFactorDisabledEvent(
            $userId,
            $email,
            $this->faker->uuid()
        );

        $this->assertSame(
            ['userId' => $userId, 'email' => $email],
            $event->toPrimitives()
        );
    }

    public function testFromPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = '2026-02-11T12:00:00+00:00';

        $event = TwoFactorDisabledEvent::fromPrimitives(
            ['userId' => $userId, 'email' => $email],
            $eventId,
            $occurredOn
        );

        $this->assertInstanceOf(
            TwoFactorDisabledEvent::class,
            $event
        );
        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($eventId, $event->eventId());
        $this->assertSame($occurredOn, $event->occurredOn());
    }
}
