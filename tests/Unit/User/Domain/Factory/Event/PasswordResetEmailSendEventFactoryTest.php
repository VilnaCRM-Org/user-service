<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactory;
use App\User\Domain\Factory\UserFactory;

final class PasswordResetEmailSendEventFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken('abc123', $user->getId(), $expiresAt, $createdAt);
        $eventId = 'event123';
        $factory = new PasswordResetEmailSendEventFactory();

        $event = $factory->create($token, $user, $eventId);

        $this->assertInstanceOf(PasswordResetEmailSentEvent::class, $event);
        $this->assertSame($token, $event->token);
        $this->assertSame($user->getEmail(), $event->email);
        $this->assertSame($eventId, $event->eventId());
    }
}
