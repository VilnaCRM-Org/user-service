<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\RecoveryCode;
use DateTimeImmutable;

final class RecoveryCodeTest extends UnitTestCase
{
    public function testConstructorStoresCodeAsPasswordHash(): void
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
        $hashInfo = password_get_info($recoveryCode->getCodeHash());
        $this->assertSame('argon2id', (string) $hashInfo['algoName']);
        $this->assertTrue($recoveryCode->matchesCode($plainCode));
        $this->assertFalse(
            $recoveryCode->matchesCode(strtolower($this->faker->bothify('????-????')))
        );
        $this->assertNull($recoveryCode->getUsedAt());
        $this->assertFalse($recoveryCode->isUsed());
    }

    public function testMatchesCodeIsCaseInsensitive(): void
    {
        $upperCode = strtoupper($this->faker->bothify('????-????'));
        $recoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $upperCode
        );

        $this->assertTrue($recoveryCode->matchesCode($upperCode));
        $this->assertTrue($recoveryCode->matchesCode(strtolower($upperCode)));
    }

    public function testMatchesCodeSupportsLegacySha256Hashes(): void
    {
        $plainCode = strtoupper($this->faker->bothify('????-####'));
        $recoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $plainCode
        );

        $reflection = new \ReflectionProperty($recoveryCode, 'codeHash');
        $reflection->setValue($recoveryCode, hash('sha256', strtolower($plainCode)));

        $this->assertTrue($recoveryCode->matchesCode($plainCode));
        $this->assertFalse($recoveryCode->matchesCode('ZZZZ-9999'));
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

    public function testIsValidFormatReturnsTrueForValidCode(): void
    {
        $this->assertTrue(RecoveryCode::isValidFormat('ABCD-EF12'));
        $this->assertTrue(RecoveryCode::isValidFormat('abcd-ef12'));
        $this->assertTrue(RecoveryCode::isValidFormat('1234-5678'));
        $this->assertTrue(RecoveryCode::isValidFormat('AaBb-CcDd'));
    }

    public function testIsValidFormatReturnsFalseForInvalidCodes(): void
    {
        $this->assertFalse(RecoveryCode::isValidFormat(''));
        $this->assertFalse(RecoveryCode::isValidFormat('ABCDEF12'));
        $this->assertFalse(RecoveryCode::isValidFormat('ABC-DEF'));
        $this->assertFalse(RecoveryCode::isValidFormat('ABCDE-FGHIJ'));
        $this->assertFalse(RecoveryCode::isValidFormat('AB!D-EF12'));
        $this->assertFalse(RecoveryCode::isValidFormat('ABCD-EF1'));
        $this->assertFalse(RecoveryCode::isValidFormat('ABCD-EF123'));
    }
}
