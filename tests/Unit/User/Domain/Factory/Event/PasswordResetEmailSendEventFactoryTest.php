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
        $user = $this->createUser();
        $token = $this->createPasswordResetToken($user->getId());
        $eventId = $this->faker->uuid();
        $factory = new PasswordResetEmailSendEventFactory();

        $event = $factory->create($token, $user, $eventId);

        $this->assertInstanceOf(PasswordResetEmailSentEvent::class, $event);
        $this->assertSame($token->getTokenValue(), $event->tokenValue);
        $this->assertSame($token->getUserID(), $event->userId);
        $this->assertSame($user->getEmail(), $event->email);
        $this->assertSame($eventId, $event->eventId());
    }

    private function createUser(): \App\User\Domain\Entity\UserInterface
    {
        $userFactory = new UserFactory();

        return $userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->lexify('??'),
            $this->faker->password(),
            new Uuid($this->faker->uuid())
        );
    }

    private function createPasswordResetToken(string $userId): PasswordResetToken
    {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));

        return new PasswordResetToken($this->faker->sha256(), $userId, $expiresAt, $createdAt);
    }
}
