<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PendingTwoFactor;
use DateTimeImmutable;

final class PendingTwoFactorTest extends UnitTestCase
{
    public function testConstructorSetsDefaultExpiryToFiveMinutes(): void
    {
        $createdAt = new DateTimeImmutable();
        $id = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $pendingTwoFactor = new PendingTwoFactor(
            $id,
            $userId,
            $createdAt
        );

        $this->assertSame($id, $pendingTwoFactor->getId());
        $this->assertSame($userId, $pendingTwoFactor->getUserId());
        $this->assertSame($createdAt, $pendingTwoFactor->getCreatedAt());
        $this->assertEquals(
            $createdAt->modify('+5 minutes'),
            $pendingTwoFactor->getExpiresAt()
        );
    }

    public function testIsExpiredReturnsExpectedStatus(): void
    {
        $createdAt = new DateTimeImmutable();
        $pendingTwoFactor = new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $createdAt
        );

        $this->assertFalse($pendingTwoFactor->isExpired($createdAt->modify('+1 minute')));
        $this->assertFalse($pendingTwoFactor->isExpired($createdAt->modify('+5 minutes')));
        $this->assertTrue($pendingTwoFactor->isExpired($createdAt->modify('+6 minutes')));
    }

    public function testCustomExpiryOverridesDefaultTtl(): void
    {
        $createdAt = new DateTimeImmutable();
        $customExpiry = $createdAt->modify('+30 minutes');

        $pendingTwoFactor = new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $createdAt,
            $customExpiry
        );

        $this->assertSame($customExpiry, $pendingTwoFactor->getExpiresAt());
    }
}
