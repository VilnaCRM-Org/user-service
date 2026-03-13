<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RefreshTokenDto;

final class RefreshTokenDtoTest extends UnitTestCase
{
    public function testRefreshTokenValueReturnsString(): void
    {
        $refreshToken = $this->faker->sha256();
        $dto = new RefreshTokenDto($refreshToken);

        $this->assertSame($refreshToken, $dto->refreshTokenValue());
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new RefreshTokenDto();

        $this->assertSame('', $dto->refreshToken);
    }
}
