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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->factoryMock = $this->createMock(PasswordResetEmailSendEventFactoryInterface::class);
    }

    public function testConstruction(): void
    {
        $user = $this->createUser();
        $token = $this->createPasswordResetToken($user->getId());

        $passwordResetEmail = new PasswordResetEmail($token, $user, $this->factoryMock);

        $this->assertSame($token, $passwordResetEmail->token);
        $this->assertSame($user, $passwordResetEmail->user);
    }

    public function testSend(): void
    {
        $user = $this->createUser();
        $token = $this->createPasswordResetToken($user->getId());
        $eventID = $this->faker->uuid();

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($token, $user, $eventID);

        $passwordResetEmail = new PasswordResetEmail($token, $user, $this->factoryMock);
        $passwordResetEmail->send($eventID);
    }

    private function createUser(): \App\User\Domain\Entity\User
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
