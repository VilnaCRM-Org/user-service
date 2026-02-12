<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\RecoveryCode;
use DateTimeImmutable;

final class RecoveryCodeTest extends UnitTestCase
{
    public function testConstructorStoresCodeAsSha256Hash(): void
    {
        $plainCode = strtolower($this->faker->bothify('????-????'));
        $id = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $recoveryCode = new RecoveryCode(
            $id,
            $userId,
            $plainCode
        );

        $this->assertSame($id, $recoveryCode->getId());
        $this->assertSame($userId, $recoveryCode->getUserId());
        $this->assertNotSame($plainCode, $recoveryCode->getCodeHash());
        $this->assertSame(hash('sha256', $plainCode), $recoveryCode->getCodeHash());
        $this->assertTrue($recoveryCode->matchesCode($plainCode));
        $this->assertFalse(
            $recoveryCode->matchesCode(strtolower($this->faker->bothify('????-????')))
        );
        $this->assertNull($recoveryCode->getUsedAt());
        $this->assertFalse($recoveryCode->isUsed());
    }

    public function testMarkAsUsedStoresTimestamp(): void
    {
        $recoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $this->faker->uuid(),
            strtolower($this->faker->bothify('????-????'))
        );
        $usedAt = new DateTimeImmutable();

        $recoveryCode->markAsUsed($usedAt);

        $this->assertSame($usedAt, $recoveryCode->getUsedAt());
        $this->assertTrue($recoveryCode->isUsed());
    }

    public function testMarkAsUsedWithoutTimestampUsesCurrentTime(): void
    {
        $recoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $this->faker->uuid(),
            strtolower($this->faker->bothify('????-????'))
        );
        $beforeMark = new DateTimeImmutable();
        $recoveryCode->markAsUsed();
        $afterMark = new DateTimeImmutable();

        $this->assertNotNull($recoveryCode->getUsedAt());
        $this->assertGreaterThanOrEqual($beforeMark, $recoveryCode->getUsedAt());
        $this->assertLessThanOrEqual($afterMark, $recoveryCode->getUsedAt());
    }
}
