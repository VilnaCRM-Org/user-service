<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\ValueObject\UserUpdate;

final class UserUpdateDataTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();

        $updateData = new UserUpdate(
            $email,
            $initials,
            $newPassword,
            $oldPassword,
        );

        $this->assertEquals($email, $updateData->newEmail);
        $this->assertEquals($initials, $updateData->newInitials);
        $this->assertEquals($newPassword, $updateData->newPassword);
        $this->assertEquals($oldPassword, $updateData->oldPassword);
    }
}
