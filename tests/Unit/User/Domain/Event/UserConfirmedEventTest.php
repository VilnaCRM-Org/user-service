<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserConfirmedEvent;

final class UserConfirmedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $tokenValue = $this->faker->sha256();
        $eventId = $this->faker->uuid();

        $event = new UserConfirmedEvent($tokenValue, $eventId);

        $this->assertEquals($tokenValue, $event->tokenValue);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $tokenValue = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserConfirmedEvent($tokenValue, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = UserConfirmedEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals('user.confirmed', UserConfirmedEvent::eventName());
    }

    public function testOccurredOn(): void
    {
        $tokenValue = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserConfirmedEvent($tokenValue, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $tokenValue = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserConfirmedEvent($tokenValue, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
