<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordChangedEvent;

final class PasswordChangedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new PasswordChangedEvent($email, $eventId);

        $this->assertEquals($email, $event->email);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new PasswordChangedEvent($email, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = PasswordChangedEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals(
            'password.changed',
            PasswordChangedEvent::eventName()
        );
    }

    public function testOccurredOn(): void
    {
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new PasswordChangedEvent($email, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new PasswordChangedEvent($email, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
