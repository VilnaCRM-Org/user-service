<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\AuthRefreshToken;
use DateTimeImmutable;

final class AuthRefreshTokenTest extends UnitTestCase
{
    public function testConstructorStoresTokenAsSha256Hash(): void
    {
        $plainToken = $this->faker->sha256();
        $expiresAt = new DateTimeImmutable('+1 month');

        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $plainToken,
            $expiresAt
        );

        $this->assertNotEmpty($refreshToken->getId());
        $this->assertNotEmpty($refreshToken->getSessionId());
        $this->assertSame($expiresAt, $refreshToken->getExpiresAt());
        $this->assertNotSame($plainToken, $refreshToken->getTokenHash());
        $this->assertSame(
            hash('sha256', $plainToken),
            $refreshToken->getTokenHash()
        );
        $this->assertFalse($refreshToken->isGraceUsed());
        $this->assertNull($refreshToken->getRotatedAt());
    }

    public function testRotateUpdatesTokenHashAndRotationTime(): void
    {
        $expiresAt = new DateTimeImmutable('+1 month');
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            $expiresAt
        );

        $newToken = $this->faker->sha256();
        $rotatedAt = new DateTimeImmutable();

        $refreshToken->rotate($newToken, $rotatedAt);

        $this->assertSame(hash('sha256', $newToken), $refreshToken->getTokenHash());
        $this->assertSame($rotatedAt, $refreshToken->getRotatedAt());
    }

    public function testRotateResetsGraceUsedFlag(): void
    {
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );

        $refreshToken->markGraceUsed();
        $this->assertTrue($refreshToken->isGraceUsed());

        $refreshToken->rotate($this->faker->sha256(), new DateTimeImmutable());

        $this->assertFalse($refreshToken->isGraceUsed());
    }

    public function testRotateWithoutTimestampUsesCurrentTime(): void
    {
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );

        $beforeRotate = new DateTimeImmutable();
        $refreshToken->rotate($this->faker->sha256());
        $afterRotate = new DateTimeImmutable();

        $this->assertNotNull($refreshToken->getRotatedAt());
        $this->assertGreaterThanOrEqual($beforeRotate, $refreshToken->getRotatedAt());
        $this->assertLessThanOrEqual($afterRotate, $refreshToken->getRotatedAt());
    }

    public function testWithinGracePeriodDependsOnRotationTimestamp(): void
    {
        $expiresAt = new DateTimeImmutable('+1 month');
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            $expiresAt
        );
        $rotatedAt = new DateTimeImmutable();
        $refreshToken->rotate($this->faker->sha256(), $rotatedAt);

        $this->assertTrue(
            $refreshToken->isWithinGracePeriod($rotatedAt->modify('+30 seconds'), 60)
        );
        $this->assertTrue(
            $refreshToken->isWithinGracePeriod($rotatedAt->modify('+60 seconds'), 60)
        );
        $this->assertFalse(
            $refreshToken->isWithinGracePeriod($rotatedAt->modify('+90 seconds'), 60)
        );
    }

    public function testWithinGracePeriodReturnsFalseWithoutRotation(): void
    {
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );

        $this->assertFalse($refreshToken->isWithinGracePeriod(new DateTimeImmutable(), 60));
        $this->assertFalse($refreshToken->isWithinGracePeriod(new DateTimeImmutable(), -1));
    }

    public function testZeroGraceWindowAllowsOnlyTheRotationInstant(): void
    {
        $rotatedAt = new DateTimeImmutable();
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );

        $refreshToken->rotate($this->faker->sha256(), $rotatedAt);

        $this->assertTrue($refreshToken->isWithinGracePeriod($rotatedAt, 0));
        $this->assertFalse(
            $refreshToken->isWithinGracePeriod($rotatedAt->modify('+1 second'), 0)
        );
    }

    public function testGraceUsageCanBeMarked(): void
    {
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );

        $refreshToken->markGraceUsed();

        $this->assertTrue($refreshToken->isGraceUsed());
    }

    public function testCanBeRevokedAndExpired(): void
    {
        $expiresAt = new DateTimeImmutable('+10 minutes');
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->sha256(),
            $expiresAt
        );

        $this->assertFalse($refreshToken->isExpired($expiresAt->modify('-5 minutes')));
        $this->assertFalse($refreshToken->isExpired($expiresAt));
        $this->assertTrue($refreshToken->isExpired($expiresAt->modify('+1 minute')));
        $this->assertNull($refreshToken->getRevokedAt());

        $revokedAt = new DateTimeImmutable();
        $refreshToken->revoke($revokedAt);

        $this->assertSame($revokedAt, $refreshToken->getRevokedAt());
    }

    public function testMatchesToken(): void
    {
        $plainToken = $this->faker->sha256();
        $refreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $plainToken,
            new DateTimeImmutable('+1 month')
        );

        $this->assertTrue($refreshToken->matchesToken($plainToken));
        $this->assertFalse($refreshToken->matchesToken($this->faker->sha256()));
    }
}
