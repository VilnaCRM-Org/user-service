<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\SignOutDto;

final class SignOutDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $dto = new SignOutDto();

        $this->assertInstanceOf(SignOutDto::class, $dto);
    }
}
