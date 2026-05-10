<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\CompleteTwoFactorDto;

final class CompleteTwoFactorDtoTest extends UnitTestCase
{
    public function testPendingSessionIdValueReturnsString(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $dto = new CompleteTwoFactorDto($pendingSessionId, $twoFactorCode);

        $this->assertSame($pendingSessionId, $dto->pendingSessionIdValue());
    }

    public function testTwoFactorCodeValueReturnsString(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $dto = new CompleteTwoFactorDto($pendingSessionId, $twoFactorCode);

        $this->assertSame($twoFactorCode, $dto->twoFactorCodeValue());
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new CompleteTwoFactorDto();

        $this->assertSame('', $dto->pendingSessionId);
        $this->assertSame('', $dto->twoFactorCode);
    }
}
