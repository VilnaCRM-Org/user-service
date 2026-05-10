<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserDeletedEvent;

final class UserDeletedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new UserDeletedEvent($userId, $email, $eventId);

        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($email, $event->email);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserDeletedEvent($userId, $email, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = UserDeletedEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals('user.deleted', UserDeletedEvent::eventName());
    }

    public function testOccurredOn(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserDeletedEvent($userId, $email, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserDeletedEvent($userId, $email, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
