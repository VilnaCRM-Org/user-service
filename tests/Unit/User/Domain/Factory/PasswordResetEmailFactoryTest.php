<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Aggregate\PasswordResetEmail;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;
use App\User\Domain\Factory\PasswordResetEmailFactory;
use App\User\Domain\Factory\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;

final class PasswordResetEmailFactoryTest extends UnitTestCase
{
    private MockObject|PasswordResetEmailSendEventFactoryInterface $eventFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventFactoryMock = $this->createMock(
            PasswordResetEmailSendEventFactoryInterface::class
        );
    }

    public function testCreate(): void
    {
        $user = $this->createTestUser('user@example.com', 'JD', '123e4567-e89b-12d3-a456-426614174000');
        $token = $this->createTestToken('abc123token', $user->getId());
        $factory = new PasswordResetEmailFactory($this->eventFactoryMock);

        $passwordResetEmail = $factory->create($token, $user);

        $this->assertInstanceOf(PasswordResetEmail::class, $passwordResetEmail);
        $this->assertSame($token, $passwordResetEmail->token);
        $this->assertSame($user, $passwordResetEmail->user);
    }

    public function testCreateWithDifferentData(): void
    {
        $user = $this->createTestUser('another@example.org', 'AB', '456e7890-e89b-12d3-a456-426614174001');
        $token = $this->createTestToken('xyz789token', $user->getId());
        $factory = new PasswordResetEmailFactory($this->eventFactoryMock);

        $passwordResetEmail = $factory->create($token, $user);

        $this->assertInstanceOf(PasswordResetEmail::class, $passwordResetEmail);
        $this->assertSame($token, $passwordResetEmail->token);
        $this->assertSame($user, $passwordResetEmail->user);
    }

    private function createTestUser(
        string $email,
        string $initials,
        string $uuid
    ): User {
        $userFactory = new UserFactory();
        return $userFactory->create(
            $email,
            $initials,
            'password123',
            new Uuid($uuid)
        );
    }

    private function createTestToken(
        string $token,
        Uuid $userId
    ): PasswordResetToken {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        return new PasswordResetToken(
            $token,
            $userId,
            $expiresAt,
            $createdAt
        );
    }
}
