<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Aggregate;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Aggregate\PasswordResetEmail;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;

final class PasswordResetEmailTest extends UnitTestCase
{
    private MockObject|PasswordResetEmailSendEventFactoryInterface $factoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factoryMock = $this->createMock(PasswordResetEmailSendEventFactoryInterface::class);
    }

    public function testConstruction(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken('abc123', $user->getId(), $expiresAt, $createdAt);

        $passwordResetEmail = new PasswordResetEmail($token, $user, $this->factoryMock);

        $this->assertSame($token, $passwordResetEmail->token);
        $this->assertSame($user, $passwordResetEmail->user);
    }

    public function testSend(): void
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create('user@example.com', 'JD', 'password123', new Uuid('123e4567-e89b-12d3-a456-426614174000'));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken('abc123', $user->getId(), $expiresAt, $createdAt);
        $eventID = 'event123';

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($token, $user, $eventID);

        $passwordResetEmail = new PasswordResetEmail($token, $user, $this->factoryMock);
        $passwordResetEmail->send($eventID);
    }
}
