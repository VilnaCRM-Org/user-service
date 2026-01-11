<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmPasswordResetDto;

final class ConfirmPasswordResetDtoTest extends UnitTestCase
{
    public function testConstructWithAllParameters(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();

        $dto = new ConfirmPasswordResetDto($token, $newPassword);

        $this->assertInstanceOf(ConfirmPasswordResetDto::class, $dto);
        $this->assertSame($token, $dto->token);
        $this->assertSame($newPassword, $dto->newPassword);
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new ConfirmPasswordResetDto();

        $this->assertInstanceOf(ConfirmPasswordResetDto::class, $dto);
        $this->assertSame('', $dto->token);
        $this->assertSame('', $dto->newPassword);
    }

    public function testConstructWithPartialParameters(): void
    {
        $token = $this->faker->sha256();

        $dto = new ConfirmPasswordResetDto($token);

        $this->assertInstanceOf(ConfirmPasswordResetDto::class, $dto);
        $this->assertSame($token, $dto->token);
        $this->assertSame('', $dto->newPassword);
    }
}
