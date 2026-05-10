<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetRequestedEvent;

final class PasswordResetRequestedEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $token = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $event = new PasswordResetRequestedEvent($userId, $userEmail, $token, $eventId);

        $this->assertSame($userId, $event->userId);
        $this->assertSame($userEmail, $event->userEmail);
        $this->assertSame($token, $event->token);
        $this->assertSame($eventId, $event->eventId());
    }

    public function testEventName(): void
    {
        $eventName = PasswordResetRequestedEvent::eventName();

        $this->assertSame('user.password_reset_requested', $eventName);
    }

    public function testToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $token = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $event = new PasswordResetRequestedEvent($userId, $userEmail, $token, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertArrayHasKey('userEmail', $primitives);
        $this->assertArrayHasKey('token', $primitives);
        $this->assertSame($userId, $primitives['userId']);
        $this->assertSame($userEmail, $primitives['userEmail']);
        $this->assertSame($token, $primitives['token']);
    }

    public function testFromPrimitivesAndToPrimitives(): void
    {
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $token = $this->faker->sha256();
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->dateTime()->format('Y-m-d H:i:s');

        $event = new PasswordResetRequestedEvent(
            $userId,
            $userEmail,
            $token,
            $eventId,
            $occurredOn
        );

        $serializedEvent = $event->toPrimitives();
        $deserializedEvent = PasswordResetRequestedEvent::fromPrimitives(
            $serializedEvent,
            $eventId,
            $occurredOn
        );

        $this->assertEquals($event, $deserializedEvent);
    }
}
