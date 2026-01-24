<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetEmailSentEvent;

final class PasswordResetEmailSentEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $tokenValue = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $event = new PasswordResetEmailSentEvent($tokenValue, $userId, $email, $eventId);

        $this->assertSame($tokenValue, $event->tokenValue);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($email, $event->email);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $eventName = PasswordResetEmailSentEvent::eventName();

        $this->assertSame('user.password_reset_email_sent', $eventName);
    }

    public function testToPrimitives(): void
    {
        $tokenValue = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $event = new PasswordResetEmailSentEvent($tokenValue, $userId, $email, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('tokenValue', $primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertArrayHasKey('email', $primitives);
        $this->assertSame($tokenValue, $primitives['tokenValue']);
        $this->assertSame($userId, $primitives['userId']);
        $this->assertSame($email, $primitives['email']);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $tokenValue = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->dateTime()->format('Y-m-d H:i:s');

        $event = new PasswordResetEmailSentEvent(
            $tokenValue,
            $userId,
            $email,
            $eventId,
            $occurredOn
        );

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = PasswordResetEmailSentEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }
}
