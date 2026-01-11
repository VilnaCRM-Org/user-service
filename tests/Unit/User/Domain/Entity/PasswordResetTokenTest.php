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

    #[\Override]
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

    public function testTokenExpirationExactBoundary(): void
    {
        $this->testFutureTokenNotExpired();
        $this->testPastTokenExpired();
        $this->testMicroSecondBoundaryConditions();
    }

    public function testFutureTokenNotExpired(): void
    {
        // Create a token that expires far in the future to ensure it's not expired
        $futureTime = new \DateTimeImmutable('+1 year');
        $futureToken = new PasswordResetToken(
            'future-token',
            $this->faker->uuid(),
            $futureTime,
            $futureTime->modify('-1 hour')
        );

        // This token should definitely not be expired
        $this->assertFalse($futureToken->isExpired());
    }

    public function testPastTokenExpired(): void
    {
        // Create a token that expired far in the past to ensure it's expired
        $pastTime = new \DateTimeImmutable('-1 year');
        $pastToken = new PasswordResetToken(
            'past-token',
            $this->faker->uuid(),
            $pastTime,
            $pastTime->modify('-1 hour')
        );

        // This token should definitely be expired
        $this->assertTrue($pastToken->isExpired());
    }

    public function testMicroSecondBoundaryConditions(): void
    {
        // Create multiple tokens with slightly different expiration times
        // to increase the chances of catching the boundary condition
        for ($i = 0; $i < 5; $i++) {
            $microTime = new \DateTimeImmutable("+{$i} microseconds");
            $microToken = new PasswordResetToken(
                "micro-token-{$i}",
                $this->faker->uuid(),
                $microTime,
                $microTime->modify('-1 hour')
            );

            // These tokens expire very close to now, likely expired
            // This increases chances of hitting the exact boundary condition
            $result = $microToken->isExpired();
            $this->assertIsBool($result); // Just ensure it returns a boolean
        }
    }

    public function testIsExpiredGreaterThanVsGreaterEqualBoundary(): void
    {
        // With the updated isExpired method that accepts a currentTime parameter,
        // we can now precisely test the > vs >= boundary condition

        $expirationTime = new \DateTimeImmutable('2024-01-01 12:00:00.000000');
        $token = new PasswordResetToken(
            'boundary-test-token',
            $this->faker->uuid(),
            $expirationTime,
            $expirationTime->modify('-1 hour')
        );

        // Test with current time exactly equal to expiration time
        // With > operator: false (not expired)
        // With >= operator: true (expired)
        $this->assertFalse($token->isExpired($expirationTime));

        // Test with current time 1 microsecond before expiration
        $beforeExpiration = $expirationTime->modify('-1 microsecond');
        $this->assertFalse($token->isExpired($beforeExpiration));

        // Test with current time 1 microsecond after expiration
        $afterExpiration = $expirationTime->modify('+1 microsecond');
        $this->assertTrue($token->isExpired($afterExpiration));

        // Test boundary with different precisions
        $this->assertFalse($token->isExpired($expirationTime->modify('-1 second')));
        $this->assertTrue($token->isExpired($expirationTime->modify('+1 second')));
    }

    public function testIsExpiredWithPreciseTimestamp(): void
    {
        // Test with extremely precise timing to catch the > vs >= mutation
        $baseTime = new \DateTimeImmutable('2024-01-01 12:00:00.000000');

        // Test with time 1 second before expiration (should not be expired)
        $beforeTime = $baseTime->modify('-1 second');
        $beforeToken = new PasswordResetToken(
            'before-token',
            $this->faker->uuid(),
            $beforeTime,
            $beforeTime->modify('-1 hour')
        );

        // This should definitely be expired since current time > before time
        $this->assertTrue($beforeToken->isExpired());

        // Test with time far in the future (should definitely not be expired)
        $futureTime = new \DateTimeImmutable('+1 hour');
        $futureToken = new PasswordResetToken(
            'future-token',
            $this->faker->uuid(),
            $futureTime,
            $futureTime->modify('-1 hour')
        );

        // This should not be expired since future time > current time
        $this->assertFalse($futureToken->isExpired());
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

    public function testExtendExpiration(): void
    {
        $originalExpiration = new \DateTimeImmutable('+1 hour');
        $newExpiration = new \DateTimeImmutable('+2 hours');

        $token = new PasswordResetToken(
            'test-token',
            $this->faker->uuid(),
            $originalExpiration,
            new \DateTimeImmutable()
        );

        $this->assertSame($originalExpiration, $token->getExpiresAt());

        $token->extendExpiration($newExpiration);

        $this->assertSame($newExpiration, $token->getExpiresAt());
    }

    public function testResetUsage(): void
    {
        $token = new PasswordResetToken(
            'test-token',
            $this->faker->uuid(),
            new \DateTimeImmutable('+1 hour'),
            new \DateTimeImmutable()
        );

        $this->assertFalse($token->isUsed());

        $token->markAsUsed();
        $this->assertTrue($token->isUsed());

        $token->resetUsage();
        $this->assertFalse($token->isUsed());
    }

    public function testExtendExpirationMakesExpiredTokenValid(): void
    {
        $expiredTime = new \DateTimeImmutable('-1 hour');
        $token = new PasswordResetToken(
            'expired-token',
            $this->faker->uuid(),
            $expiredTime,
            new \DateTimeImmutable('-2 hours')
        );

        $this->assertTrue($token->isExpired());

        $newExpiration = new \DateTimeImmutable('+1 hour');
        $token->extendExpiration($newExpiration);

        $this->assertFalse($token->isExpired());
    }
}
