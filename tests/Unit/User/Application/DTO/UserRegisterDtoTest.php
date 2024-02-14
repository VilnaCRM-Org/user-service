<?php

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserRegisterDto;

class UserRegisterDtoTest extends UnitTestCase
{
    public function testConstructWithValidData(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = new UserRegisterDto($email, $initials, $password);

        $this->assertEquals($email, $user->email);
        $this->assertEquals($initials, $user->initials);
        $this->assertEquals($password, $user->password);
    }
}
