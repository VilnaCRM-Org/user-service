<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\AuthSession;
use DateTimeImmutable;

final class AuthSessionTest extends UnitTestCase
{
    public function testConstructorStoresSessionData(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');

        $session = new AuthSession(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $createdAt,
            $expiresAt,
            true
        );

        $this->assertNotEmpty($session->getId());
        $this->assertNotEmpty($session->getUserId());
        $this->assertNotEmpty($session->getIpAddress());
        $this->assertNotEmpty($session->getUserAgent());
        $this->assertSame($createdAt, $session->getCreatedAt());
        $this->assertSame($expiresAt, $session->getExpiresAt());
        $this->assertNull($session->getRevokedAt());
        $this->assertTrue($session->isRememberMe());
        $this->assertFalse($session->isRevoked());
    }

    public function testRevokeMarksSessionAsRevoked(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');
        $revokedAt = $createdAt->modify('+10 minutes');

        $session = new AuthSession(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $createdAt,
            $expiresAt,
            false
        );

        $session->revoke($revokedAt);

        $this->assertSame($revokedAt, $session->getRevokedAt());
        $this->assertTrue($session->isRevoked());
    }

    public function testRevokeWithoutParameterUsesCurrentTime(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+1 hour');
        $session = new AuthSession(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $createdAt,
            $expiresAt,
            false
        );

        $beforeRevoke = new DateTimeImmutable();
        $session->revoke();
        $afterRevoke = new DateTimeImmutable();

        $this->assertNotNull($session->getRevokedAt());
        $this->assertGreaterThanOrEqual($beforeRevoke, $session->getRevokedAt());
        $this->assertLessThanOrEqual($afterRevoke, $session->getRevokedAt());
    }

    public function testIsExpiredReturnsTrueAfterExpiry(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+10 minutes');

        $session = new AuthSession(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $createdAt,
            $expiresAt,
            false
        );

        $this->assertFalse($session->isExpired($createdAt->modify('+5 minutes')));
        $this->assertFalse($session->isExpired($expiresAt));
        $this->assertTrue($session->isExpired($createdAt->modify('+15 minutes')));
    }
}
