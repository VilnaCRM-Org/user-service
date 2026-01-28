<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\ConfirmationEmailSentEvent;

final class ConfirmationEmailSendEventTest extends UnitTestCase
{
    public function testCreateEvent(): void
    {
        $tokenValue = $this->faker->sha256();
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event =
            new ConfirmationEmailSentEvent($tokenValue, $emailAddress, $eventId);

        $this->assertEquals($tokenValue, $event->tokenValue);
        $this->assertEquals($emailAddress, $event->emailAddress);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $tokenValue = $this->faker->sha256();
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSentEvent(
            $tokenValue,
            $emailAddress,
            $eventId,
            $occurredOn
        );

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = ConfirmationEmailSentEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }

    public function testEventName(): void
    {
        $this->assertEquals(
            'confirmation_email.send',
            ConfirmationEmailSentEvent::eventName()
        );
    }

    public function testOccurredOn(): void
    {
        $tokenValue = $this->faker->sha256();
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSentEvent(
            $tokenValue,
            $emailAddress,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventId(): void
    {
        $tokenValue = $this->faker->sha256();
        $emailAddress = $this->faker->email();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->date();

        $event = new ConfirmationEmailSentEvent(
            $tokenValue,
            $emailAddress,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($eventId, $event->eventId());
    }
}
