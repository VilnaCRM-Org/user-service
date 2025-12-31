<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Factory\UserFactory;

final class PasswordResetEmailSentEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken('abc123', $user->getId(), $expiresAt, $createdAt);
        $email = 'user@example.com';
        $eventId = 'event123';

        $event = new PasswordResetEmailSentEvent($token, $email, $eventId);

        $this->assertSame($token, $event->token);
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
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken('abc123', $user->getId(), $expiresAt, $createdAt);
        $email = 'user@example.com';
        $eventId = 'event123';

        $event = new PasswordResetEmailSentEvent($token, $email, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('tokenValue', $primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertArrayHasKey('email', $primitives);
        $this->assertSame('abc123', $primitives['tokenValue']);
        $this->assertSame($user->getId(), $primitives['userId']);
        $this->assertSame($email, $primitives['email']);
    }

    public function testFromPrimitivesThrowsException(): void
    {
        $body = [
            'tokenValue' => 'abc123',
            'userId' => '123e4567-e89b-12d3-a456-426614174000',
            'email' => 'user@example.com',
        ];
        $eventId = 'event123';
        $occurredOn = '2023-01-01 12:00:00';

        $this->expectException(\RuntimeException::class);

        PasswordResetEmailSentEvent::fromPrimitives($body, $eventId, $occurredOn);
    }
}
