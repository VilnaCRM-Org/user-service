<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\PasswordResetTokenFactory;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;

final class PasswordResetTokenTest extends UnitTestCase
{
    private PasswordResetToken $passwordResetToken;
    private PasswordResetTokenFactoryInterface $passwordResetTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenFactory = new PasswordResetTokenFactory(
            32, // tokenLength
            1   // expirationTimeInHours
        );
        $this->passwordResetToken = $this->passwordResetTokenFactory->create($this->faker->uuid());
    }

    public function testCreatedTokenIsNotUsed(): void
    {
        $this->assertFalse($this->passwordResetToken->isUsed());
    }

    public function testCreatedTokenIsNotExpired(): void
    {
        $this->assertFalse($this->passwordResetToken->isExpired());
    }

    public function testMarkAsUsed(): void
    {
        $this->assertFalse($this->passwordResetToken->isUsed());

        $this->passwordResetToken->markAsUsed();

        $this->assertTrue($this->passwordResetToken->isUsed());
    }

    public function testTokenExpiration(): void
    {
        // Create a token that expired 2 hours ago
        $expiredTime = new \DateTimeImmutable('-2 hours');
        $createdTime = new \DateTimeImmutable('-3 hours');

        $expiredToken = new PasswordResetToken(
            'test-token',
            $this->faker->uuid(),
            $expiredTime,
            $createdTime
        );

        $this->assertTrue($expiredToken->isExpired());
    }

    public function testTokenProperties(): void
    {
        $userID = $this->faker->uuid();
        $token = $this->passwordResetTokenFactory->create($userID);

        $this->assertSame($userID, $token->getUserID());
        $this->assertNotEmpty($token->getTokenValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getExpiresAt());
        $this->assertTrue($token->getExpiresAt() > $token->getCreatedAt());
    }
}
