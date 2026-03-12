<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RefreshTokenDto;
use LogicException;

final class RefreshTokenDtoTest extends UnitTestCase
{
    public function testRefreshTokenValueReturnsString(): void
    {
        $refreshToken = $this->faker->sha256();
        $dto = new RefreshTokenDto($refreshToken);

        $this->assertSame($refreshToken, $dto->refreshTokenValue());
    }

    public function testRefreshTokenValueThrowsForNonStringPayload(): void
    {
        $dto = new RefreshTokenDto($this->faker->numberBetween(100000, 999999));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected "refreshToken" to be a string after request validation.');

        $dto->refreshTokenValue();
    }
}
