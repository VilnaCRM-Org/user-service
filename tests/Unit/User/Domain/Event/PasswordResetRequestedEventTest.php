<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\UserFactory;

final class PasswordResetRequestedEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $token = 'abc123';
        $eventId = 'event123';
        $event = new PasswordResetRequestedEvent($user, $token, $eventId);

        $this->assertSame($user, $event->user);
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
        $userFactory = new UserFactory();
        $uuid = new Uuid('123e4567-e89b-12d3-a456-426614174000');
        $user = $userFactory->create('user@example.com', 'JD', 'password123', $uuid);
        $token = 'abc123';
        $eventId = 'event123';
        $event = new PasswordResetRequestedEvent($user, $token, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertArrayHasKey('userEmail', $primitives);
        $this->assertArrayHasKey('token', $primitives);
        $this->assertSame($user->getId(), $primitives['userId']);
        $this->assertSame($user->getEmail(), $primitives['userEmail']);
        $this->assertSame($token, $primitives['token']);
    }

    public function testFromPrimitivesThrowsException(): void
    {
        $body = [
            'userId' => '123e4567-e89b-12d3-a456-426614174000',
            'userEmail' => 'user@example.com',
            'token' => 'abc123',
        ];
        $eventId = 'event123';
        $occurredOn = '2023-01-01 12:00:00';

        $this->expectException(\RuntimeException::class);

        PasswordResetRequestedEvent::fromPrimitives($body, $eventId, $occurredOn);
    }
}
