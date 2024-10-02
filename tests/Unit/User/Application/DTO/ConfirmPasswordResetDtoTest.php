<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmPasswordResetDto;

final class ConfirmPasswordResetDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $dto = new ConfirmPasswordResetDto(
            $token = (string) $this->faker->randomNumber(6, true),
            $password = $this->faker->password()
        );

        $this->assertEquals($token, $dto->token);
        $this->assertEquals($password, $dto->newPassword);
    }
}
