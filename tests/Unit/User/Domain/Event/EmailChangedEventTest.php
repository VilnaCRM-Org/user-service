<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\EmailChangedEvent;

final class EmailChangedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $user = $this->createMock(User::class);
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new EmailChangedEvent($user, $oldEmail, $eventId);

        $this->assertEquals($user, $event->user);
        $this->assertEquals($oldEmail, $event->oldEmail);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $user = $this->createMock(User::class);
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new EmailChangedEvent($user, $oldEmail, $eventId, $occurredOn);

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
        $user = $this->createMock(User::class);
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new EmailChangedEvent($user, $oldEmail, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $user = $this->createMock(User::class);
        $oldEmail = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new EmailChangedEvent($user, $oldEmail, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
