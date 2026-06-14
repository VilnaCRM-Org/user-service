<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PendingTwoFactor;
use DateTimeImmutable;

final class PendingTwoFactorTest extends UnitTestCase
{
    public function testCreateSetsDefaultExpiryToFiveMinutes(): void
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
        $this->assertFalse($pendingTwoFactor->isRememberMe());
    }

    public function testWitherPatternSetsRememberMeFlag(): void
    {
        $createdAt = new DateTimeImmutable();

        $withRememberMe = (new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $createdAt
        ))->withRememberMe();

        $withoutRememberMe = new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $createdAt
        );

        $this->assertTrue($withRememberMe->isRememberMe());
        $this->assertFalse($withoutRememberMe->isRememberMe());
    }

    public function testWithRememberMeDoesNotMutateOriginal(): void
    {
        $createdAt = new DateTimeImmutable();
        $original = new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $createdAt
        );

        $clone = $original->withRememberMe();

        $this->assertFalse($original->isRememberMe());
        $this->assertTrue($clone->isRememberMe());
        $this->assertNotSame($original, $clone);
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

    public function testNewSessionHasNoFailedAttempts(): void
    {
        $pendingTwoFactor = $this->createPendingTwoFactor();

        $this->assertSame(0, $pendingTwoFactor->getFailedAttempts());
        $this->assertFalse($pendingTwoFactor->hasExhaustedAttempts());
    }

    public function testRecordFailedAttemptIncrementsCounter(): void
    {
        $pendingTwoFactor = $this->createPendingTwoFactor();

        $pendingTwoFactor->recordFailedAttempt();
        $pendingTwoFactor->recordFailedAttempt();

        $this->assertSame(2, $pendingTwoFactor->getFailedAttempts());
        $this->assertFalse($pendingTwoFactor->hasExhaustedAttempts());
    }

    public function testHasExhaustedAttemptsAtMaxFailedAttempts(): void
    {
        $pendingTwoFactor = $this->createPendingTwoFactor();

        for ($attempt = 0; $attempt < PendingTwoFactor::MAX_FAILED_ATTEMPTS; ++$attempt) {
            $this->assertFalse($pendingTwoFactor->hasExhaustedAttempts());
            $pendingTwoFactor->recordFailedAttempt();
        }

        $this->assertSame(
            PendingTwoFactor::MAX_FAILED_ATTEMPTS,
            $pendingTwoFactor->getFailedAttempts()
        );
        $this->assertTrue($pendingTwoFactor->hasExhaustedAttempts());
    }

    public function testSetFailedAttemptsClampsNegativeValuesToZero(): void
    {
        $pendingTwoFactor = $this->createPendingTwoFactor();

        $pendingTwoFactor->setFailedAttempts(-3);

        $this->assertSame(0, $pendingTwoFactor->getFailedAttempts());
    }

    private function createPendingTwoFactor(): PendingTwoFactor
    {
        return new PendingTwoFactor(
            $this->faker->uuid(),
            $this->faker->uuid(),
            new DateTimeImmutable()
        );
    }
}
