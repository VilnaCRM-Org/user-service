<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\DisableTwoFactorDto;

final class DisableTwoFactorDtoTest extends UnitTestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $dto = new DisableTwoFactorDto();

        $this->assertSame('', $dto->twoFactorCode);
    }

    public function testConstructorAcceptsTotpCode(): void
    {
        $dto = new DisableTwoFactorDto('123456');

        $this->assertSame('123456', $dto->twoFactorCode);
    }

    public function testConstructorAcceptsRecoveryCode(): void
    {
        $dto = new DisableTwoFactorDto('ABCD-1234');

        $this->assertSame('ABCD-1234', $dto->twoFactorCode);
    }
}
