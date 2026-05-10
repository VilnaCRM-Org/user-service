<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\EmailChangedEvent;

final class EmailChangedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $userId = $this->faker->uuid();
        $newEmail = $this->faker->email();
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new EmailChangedEvent($userId, $newEmail, $oldEmail, $eventId);

        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($newEmail, $event->newEmail);
        $this->assertEquals($oldEmail, $event->oldEmail);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $newEmail = $this->faker->email();
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new EmailChangedEvent($userId, $newEmail, $oldEmail, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = EmailChangedEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals('email.changed', EmailChangedEvent::eventName());
    }

    public function testOccurredOn(): void
    {
        $userId = $this->faker->uuid();
        $newEmail = $this->faker->email();
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new EmailChangedEvent($userId, $newEmail, $oldEmail, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $userId = $this->faker->uuid();
        $newEmail = $this->faker->email();
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new EmailChangedEvent($userId, $newEmail, $oldEmail, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
