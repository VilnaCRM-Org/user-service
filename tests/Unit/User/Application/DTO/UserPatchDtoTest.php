<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;

class UserPatchDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();

        $user = new UserPatchDto($email, $initials, $oldPassword, $newPassword);

        $this->assertEquals($email, $user->email);
        $this->assertEquals($initials, $user->initials);
        $this->assertEquals($oldPassword, $user->oldPassword);
        $this->assertEquals($newPassword, $user->newPassword);
    }
}
