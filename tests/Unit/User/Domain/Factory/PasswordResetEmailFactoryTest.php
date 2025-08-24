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
        $userFactory = new UserFactory();
        $uuid = new Uuid('123e4567-e89b-12d3-a456-426614174000');
        $user = $userFactory->create(
            'user@example.com',
            'JD',
            'password123',
            $uuid
        );
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken(
            'abc123token',
            $user->getId(),
            $expiresAt,
            $createdAt
        );
        $factory = new PasswordResetEmailFactory($this->eventFactoryMock);

        $passwordResetEmail = $factory->create($token, $user);

        $this->assertInstanceOf(PasswordResetEmail::class, $passwordResetEmail);
        $this->assertSame($token, $passwordResetEmail->token);
        $this->assertSame($user, $passwordResetEmail->user);
    }

    public function testCreateWithDifferentData(): void
    {
        $userFactory = new UserFactory();
        $uuid = new Uuid('456e7890-e89b-12d3-a456-426614174001');
        $user = $userFactory->create(
            'another@example.org',
            'AB',
            'password123',
            $uuid
        );
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken(
            'xyz789token',
            $user->getId(),
            $expiresAt,
            $createdAt
        );
        $factory = new PasswordResetEmailFactory($this->eventFactoryMock);

        $passwordResetEmail = $factory->create($token, $user);

        $this->assertInstanceOf(PasswordResetEmail::class, $passwordResetEmail);
        $this->assertSame($token, $passwordResetEmail->token);
        $this->assertSame($user, $passwordResetEmail->user);
    }
}
