<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmTwoFactorDto;

final class ConfirmTwoFactorDtoTest extends UnitTestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $dto = new ConfirmTwoFactorDto();

        $this->assertSame('', $dto->twoFactorCode);
    }

    public function testConstructorAcceptsCustomValues(): void
    {
        $code = '123456';
        $dto = new ConfirmTwoFactorDto($code);

        $this->assertSame($code, $dto->twoFactorCode);
    }

    public function testTwoFactorCodeValueReturnsString(): void
    {
        $code = (string) $this->faker->numberBetween(100000, 999999);
        $dto = new ConfirmTwoFactorDto($code);

        $this->assertSame($code, $dto->twoFactorCodeValue());
    }
}
