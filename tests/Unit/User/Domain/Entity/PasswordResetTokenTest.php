<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;

final class PasswordResetTokenTest extends UnitTestCase
{
    private PasswordResetToken $token;
    private string $tokenValue;
    private string $userID;
    private int $expiresInSeconds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenValue = $this->faker->sha256();
        $this->userID = $this->faker->uuid();
        $this->expiresInSeconds = $this->faker->numberBetween(1, 7200);
        
        $this->token = new PasswordResetToken(
            $this->tokenValue,
            $this->userID,
            $this->expiresInSeconds
        );
    }

    public function testGetTokenValue(): void
    {
        $this->assertEquals($this->tokenValue, $this->token->getTokenValue());
    }

    public function testGetUserID(): void
    {
        $this->assertEquals($this->userID, $this->token->getUserID());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->token->getCreatedAt());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $this->token->getCreatedAt());
    }

    public function testGetExpiresAt(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->token->getExpiresAt());
        
        $expectedExpiresAt = $this->token->getCreatedAt()->modify("+{$this->expiresInSeconds} seconds");
        $this->assertEquals($expectedExpiresAt, $this->token->getExpiresAt());
    }

    public function testIsExpiredReturnsFalseForValidToken(): void
    {
        $this->assertFalse($this->token->isExpired());
    }

    public function testIsExpiredReturnsTrueForExpiredToken(): void
    {
        $futureTime = $this->token->getExpiresAt()->modify('+1 second');
        $this->assertTrue($this->token->isExpired($futureTime));
    }

    public function testIsExpiredWithCustomNow(): void
    {
        $pastTime = $this->token->getCreatedAt()->modify('-1 second');
        $this->assertFalse($this->token->isExpired($pastTime));

        $futureTime = $this->token->getExpiresAt()->modify('+1 second');
        $this->assertTrue($this->token->isExpired($futureTime));
    }

    public function testSetTokenValue(): void
    {
        $newTokenValue = $this->faker->sha256();
        $this->token->setTokenValue($newTokenValue);
        
        $this->assertEquals($newTokenValue, $this->token->getTokenValue());
    }

    public function testSetUserID(): void
    {
        $newUserID = $this->faker->uuid();
        $this->token->setUserID($newUserID);
        
        $this->assertEquals($newUserID, $this->token->getUserID());
    }

    public function testSetCreatedAt(): void
    {
        $newCreatedAt = new \DateTimeImmutable('2023-01-01 10:00:00');
        $this->token->setCreatedAt($newCreatedAt);
        
        $this->assertEquals($newCreatedAt, $this->token->getCreatedAt());
    }

    public function testSetExpiresAt(): void
    {
        $newExpiresAt = new \DateTimeImmutable('2023-01-01 11:00:00');
        $this->token->setExpiresAt($newExpiresAt);
        
        $this->assertEquals($newExpiresAt, $this->token->getExpiresAt());
    }

    public function testDefaultExpirationTime(): void
    {
        $token = new PasswordResetToken($this->tokenValue, $this->userID);
        
        $expectedExpiresAt = $token->getCreatedAt()->modify('+3600 seconds');
        $this->assertEquals($expectedExpiresAt, $token->getExpiresAt());
    }

    public function testTokenExpirationCalculation(): void
    {
        $customExpiration = 1800; // 30 minutes
        $token = new PasswordResetToken($this->tokenValue, $this->userID, $customExpiration);
        
        $expectedExpiresAt = $token->getCreatedAt()->modify("+{$customExpiration} seconds");
        $this->assertEquals($expectedExpiresAt, $token->getExpiresAt());
    }
}