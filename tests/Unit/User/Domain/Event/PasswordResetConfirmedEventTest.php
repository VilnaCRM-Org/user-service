<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Event;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Factory\UserFactory;

final class PasswordResetConfirmedEventTest extends UnitTestCase
{
    public function testConstruction(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $eventId = 'event123';
        $event = new PasswordResetConfirmedEvent($user, $eventId);
        
        $this->assertSame($user, $event->user);
        $this->assertSame($eventId, $event->getEventId());
    }
    
    public function testEventName(): void
    {
        $eventName = PasswordResetConfirmedEvent::eventName();
        
        $this->assertSame('user.password_reset_confirmed', $eventName);
    }
    
    public function testToPrimitives(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $eventId = 'event123';
        $event = new PasswordResetConfirmedEvent($user, $eventId);
        $primitives = $event->toPrimitives();
        
        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('user', $primitives);
        $this->assertSame($user, $primitives['user']);
    }
    
    public function testFromPrimitives(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $body = [
            'user' => $user,
        ];
        $eventId = 'event123';
        $occurredOn = '2023-01-01 12:00:00';
        
        $event = PasswordResetConfirmedEvent::fromPrimitives($body, $eventId, $occurredOn);
        
        $this->assertInstanceOf(PasswordResetConfirmedEvent::class, $event);
        $this->assertSame($user, $event->user);
        $this->assertSame($eventId, $event->getEventId());
    }
}