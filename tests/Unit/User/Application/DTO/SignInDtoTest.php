<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\SignInDto;
use LogicException;

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

    public function testEmailValueReturnsString(): void
    {
        $email = $this->faker->safeEmail();
        $dto = new SignInDto($email, $this->faker->password());

        $this->assertSame($email, $dto->emailValue());
    }

    public function testEmailValueThrowsForNonStringPayload(): void
    {
        $dto = new SignInDto(
            $this->faker->numberBetween(100000, 999999),
            $this->faker->password()
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected "email" to be a string after request validation.');

        $dto->emailValue();
    }

    public function testPasswordValueReturnsString(): void
    {
        $password = $this->faker->password();
        $dto = new SignInDto($this->faker->safeEmail(), $password);

        $this->assertSame($password, $dto->passwordValue());
    }

    public function testPasswordValueThrowsForNonStringPayload(): void
    {
        $dto = new SignInDto($this->faker->safeEmail(), $this->faker->numberBetween(100000, 999999));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected "password" to be a string after request validation.');

        $dto->passwordValue();
    }
}
