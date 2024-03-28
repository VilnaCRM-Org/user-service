<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\UserConfirmedEvent;

class UserConfirmedEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $eventId = $this->faker->uuid();

        $event = new UserConfirmedEvent($token, $eventId);

        $this->assertEquals($token, $event->token);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserConfirmedEvent($token, $eventId, $occurredOn);

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
        $token = $this->createMock(ConfirmationToken::class);
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserConfirmedEvent($token, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new UserConfirmedEvent($token, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
