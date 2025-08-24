<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Entity\PasswordResetToken;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Factory\UserFactory;

final class PasswordResetEmailSentEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $token = new PasswordResetToken($user->getId(), 'abc123');
        $email = 'user@example.com';
        $eventId = 'event123';
        
        $event = new PasswordResetEmailSentEvent($token, $email, $eventId);
        
        $this->assertSame($token, $event->token);
        $this->assertSame($email, $event->email);
        $this->assertSame($eventId, $event->getEventId());
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
        $token = new PasswordResetToken($user->getId(), 'abc123');
        $email = 'user@example.com';
        $eventId = 'event123';
        
        $event = new PasswordResetEmailSentEvent($token, $email, $eventId);
        $primitives = $event->toPrimitives();
        
        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('token', $primitives);
        $this->assertArrayHasKey('email', $primitives);
        $this->assertSame($email, $primitives['email']);
    }
    
    public function testFromPrimitives(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $token = new PasswordResetToken($user->getId(), 'abc123');
        $body = [
            'token' => $token,
            'email' => 'user@example.com',
        ];
        $eventId = 'event123';
        $occurredOn = '2023-01-01 12:00:00';
        
        $event = PasswordResetEmailSentEvent::fromPrimitives($body, $eventId, $occurredOn);
        
        $this->assertInstanceOf(PasswordResetEmailSentEvent::class, $event);
        $this->assertSame('user@example.com', $event->email);
        $this->assertSame($eventId, $event->getEventId());
    }
}