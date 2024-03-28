<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegisteredEvent;

class UserRegisteredEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $user = $this->createMock(User::class);
        $eventId = $this->faker->uuid();

        $event = new UserRegisteredEvent($user, $eventId);

        $this->assertEquals($user, $event->user);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $user = $this->createMock(User::class);
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserRegisteredEvent($user, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = UserRegisteredEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals('user.registered', UserRegisteredEvent::eventName());
    }

    public function testOccurredOn(): void
    {
        $user = $this->createMock(User::class);
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserRegisteredEvent($user, $eventId, $occurredOn);
        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $user = $this->createMock(User::class);
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserRegisteredEvent($user, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
