<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\ConfirmationEmailSentEvent;

class ConfirmationEmailSendEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = new ConfirmationEmailSentEvent($token, $emailAddress, $eventId);

        $this->assertEquals($token, $event->token);
        $this->assertEquals($emailAddress, $event->emailAddress);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSentEvent($token, $emailAddress, $eventId, $occurredOn);

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = ConfirmationEmailSentEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent, $occurredOn);
    }

    public function testEventName(): void
    {
        $this->assertEquals('confirmation_email.send', ConfirmationEmailSentEvent::eventName());
    }

    public function testOccurredOn(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSentEvent($token, $emailAddress, $eventId, $occurredOn);

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $token = $this->createMock(ConfirmationToken::class);
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSentEvent($token, $emailAddress, $eventId, $occurredOn);

        $this->assertEquals($eventId, $event->eventId());
    }
}
