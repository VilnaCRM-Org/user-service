<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RequestPasswordResetDto;

final class RequestPasswordResetDtoTest extends UnitTestCase
{
    public function testConstructWithEmail(): void
    {
        $email = $this->faker->safeEmail();

        $dto = new RequestPasswordResetDto($email);

        $this->assertInstanceOf(RequestPasswordResetDto::class, $dto);
        $this->assertSame($email, $dto->email);
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new RequestPasswordResetDto();

        $this->assertInstanceOf(RequestPasswordResetDto::class, $dto);
        $this->assertNull($dto->email);
    }
}