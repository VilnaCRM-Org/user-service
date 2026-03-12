<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\CompleteTwoFactorDto;
use LogicException;

final class CompleteTwoFactorDtoTest extends UnitTestCase
{
    public function testPendingSessionIdValueReturnsString(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $dto = new CompleteTwoFactorDto($pendingSessionId, $twoFactorCode);

        $this->assertSame($pendingSessionId, $dto->pendingSessionIdValue());
    }

    public function testPendingSessionIdValueThrowsForNonStringPayload(): void
    {
        $dto = new CompleteTwoFactorDto(
            $this->faker->numberBetween(100000, 999999),
            (string) $this->faker->numberBetween(100000, 999999)
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected "pendingSessionId" to be a string after request validation.');

        $dto->pendingSessionIdValue();
    }

    public function testTwoFactorCodeValueReturnsString(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $dto = new CompleteTwoFactorDto($pendingSessionId, $twoFactorCode);

        $this->assertSame($twoFactorCode, $dto->twoFactorCodeValue());
    }

    public function testTwoFactorCodeValueThrowsForNonStringPayload(): void
    {
        $dto = new CompleteTwoFactorDto(
            $this->faker->uuid(),
            $this->faker->numberBetween(100000, 999999)
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected "twoFactorCode" to be a string after request validation.');

        $dto->twoFactorCodeValue();
    }
}
