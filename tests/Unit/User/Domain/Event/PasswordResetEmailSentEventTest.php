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
        $token = $this->createPasswordResetToken();
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

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
        $tokenValue = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $token = $this->createPasswordResetTokenWithValue($tokenValue, $userId);
        $email = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $event = new PasswordResetEmailSentEvent($token, $email, $eventId);
        $primitives = $event->toPrimitives();

        $this->assertIsArray($primitives);
        $this->assertArrayHasKey('tokenValue', $primitives);
        $this->assertArrayHasKey('userId', $primitives);
        $this->assertArrayHasKey('email', $primitives);
        $this->assertSame($tokenValue, $primitives['tokenValue']);
        $this->assertSame($userId, $primitives['userId']);
        $this->assertSame($email, $primitives['email']);
    }

    public function testFromPrimitivesThrowsException(): void
    {
        $body = [
            'tokenValue' => $this->faker->sha256(),
            'userId' => $this->faker->uuid(),
            'email' => $this->faker->safeEmail(),
        ];
        $eventId = $this->faker->uuid();
        $occurredOn = $this->faker->dateTime()->format('Y-m-d H:i:s');

        $this->expectException(\RuntimeException::class);

        PasswordResetEmailSentEvent::fromPrimitives($body, $eventId, $occurredOn);
    }

    private function createPasswordResetToken(): PasswordResetToken
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->lexify('??'),
            $this->faker->password(),
            new Uuid($this->faker->uuid())
        );
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));

        return new PasswordResetToken(
            $this->faker->sha256(),
            $user->getId(),
            $expiresAt,
            $createdAt
        );
    }

    private function createPasswordResetTokenWithValue(
        string $tokenValue,
        string $userId
    ): PasswordResetToken {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));

        return new PasswordResetToken($tokenValue, $userId, $expiresAt, $createdAt);
    }
}
