<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\SignOutAllDto;

final class SignOutAllDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $dto = new SignOutAllDto();

        $this->assertInstanceOf(SignOutAllDto::class, $dto);
    }
}
