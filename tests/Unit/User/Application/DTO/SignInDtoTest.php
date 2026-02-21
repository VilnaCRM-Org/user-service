<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\SignInDto;

final class SignInDtoTest extends UnitTestCase
{
    public function testConstructWithEmailAndPassword(): void
    {
        $email = $this->faker->safeEmail();
        $password = $this->faker->password();

        $dto = new SignInDto($email, $password);

        $this->assertInstanceOf(SignInDto::class, $dto);
        $this->assertSame($email, $dto->email);
        $this->assertSame($password, $dto->password);
        $this->assertFalse($dto->isRememberMe());
    }

    public function testRememberMeCanBeSetDirectly(): void
    {
        $dto = new SignInDto($this->faker->safeEmail(), $this->faker->password());
        $dto->setRememberMe(true);

        $this->assertTrue($dto->isRememberMe());
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new SignInDto();

        $this->assertInstanceOf(SignInDto::class, $dto);
        $this->assertSame('', $dto->email);
        $this->assertSame('', $dto->password);
        $this->assertFalse($dto->isRememberMe());
    }
}
