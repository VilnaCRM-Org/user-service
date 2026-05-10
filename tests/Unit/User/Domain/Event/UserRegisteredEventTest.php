<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserRegisteredEvent;

final class UserRegisteredEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $event = new UserRegisteredEvent($userId, $email, $eventId);

        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($email, $event->email);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserRegisteredEvent($userId, $email, $eventId, $occurredOn);

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
        $this->assertEquals(
            'user.registered',
            UserRegisteredEvent::eventName()
        );
    }

    public function testOccurredOn(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserRegisteredEvent($userId, $email, $eventId, $occurredOn);
        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserRegisteredEvent($userId, $email, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
