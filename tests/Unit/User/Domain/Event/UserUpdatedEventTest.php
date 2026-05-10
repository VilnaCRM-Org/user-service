<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserUpdatedEvent;

final class UserUpdatedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $previousEmail = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new UserUpdatedEvent($userId, $email, $previousEmail, $eventId);

        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($email, $event->email);
        $this->assertEquals($previousEmail, $event->previousEmail);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $previousEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserUpdatedEvent(
            $userId,
            $email,
            $previousEmail,
            $eventId,
            $occurredOn
        );

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = UserUpdatedEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals('user.updated', UserUpdatedEvent::eventName());
    }

    public function testOccurredOn(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $previousEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserUpdatedEvent(
            $userId,
            $email,
            $previousEmail,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $previousEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserUpdatedEvent(
            $userId,
            $email,
            $previousEmail,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($eventId, $event->eventId());
    }
}
