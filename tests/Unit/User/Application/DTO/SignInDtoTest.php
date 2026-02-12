<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\SignInDto;

final class SignInDtoTest extends UnitTestCase
{
    public function testConstructWithValues(): void
    {
        $email = $this->faker->safeEmail();
        $password = $this->faker->password();
        $rememberMe = $this->faker->boolean();

        $dto = new SignInDto($email, $password, $rememberMe);

        $this->assertInstanceOf(SignInDto::class, $dto);
        $this->assertSame($email, $dto->email);
        $this->assertSame($password, $dto->password);
        $this->assertSame($rememberMe, $dto->rememberMe);
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new SignInDto();

        $this->assertInstanceOf(SignInDto::class, $dto);
        $this->assertSame('', $dto->email);
        $this->assertSame('', $dto->password);
        $this->assertFalse($dto->rememberMe);
    }
}
