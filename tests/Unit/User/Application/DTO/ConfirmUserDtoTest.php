<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmUserDto;

class ConfirmUserDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $token = $this->faker->uuid();

        $user = new ConfirmUserDto($token);

        $this->assertEquals($token, $user->token);
    }
}
