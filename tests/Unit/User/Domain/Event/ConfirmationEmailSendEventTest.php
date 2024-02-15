<?php

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\ConfirmationEmailSendEvent;

class ConfirmationEmailSendEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new ConfirmationEmailSendEvent($token, $emailAddress, $eventId);

        $this->assertEquals($token, $event->token);
        $this->assertEquals($emailAddress, $event->emailAddress);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSendEvent($token, $emailAddress, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = ConfirmationEmailSendEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent, $occurredOn);
    }

    public function testEventName(): void
    {
        $this->assertEquals('confirmation_email.send', ConfirmationEmailSendEvent::eventName());
    }
}
