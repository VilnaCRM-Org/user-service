<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\UpdateUserMutationInput;

class UpdateUserMutationInputTest extends UnitTestCase
{
    public function testConstructWithAllDataAndNoValidationGroups(): void
    {
        $password = $this->faker->password();
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $newPassword = $this->faker->password();

        $input = new UpdateUserMutationInput([], $password, $initials, $email, $newPassword);

        $this->assertEquals($password, $input->password);
        $this->assertEquals($initials, $input->initials);
        $this->assertEquals($email, $input->email);
        $this->assertEquals($newPassword, $input->newPassword);
        $this->assertEquals([], $input->getValidationGroups());
    }

    public function testConstructWithSomeDataAndSpecificGroups(): void
    {
        $password = $this->faker->password();
        $email = $this->faker->email();

        $input = new UpdateUserMutationInput([UpdateUserMutationInput::EMAIL_NOT_NULL], $password, null, $email, null);

        $this->assertEquals($password, $input->password);
        $this->assertNull($input->initials);
        $this->assertEquals($email, $input->email);
        $this->assertNull($input->newPassword);
        $this->assertEquals([UpdateUserMutationInput::EMAIL_NOT_NULL], $input->getValidationGroups());
    }

    public function testGetValidationGroups()
    {
        $input = new UpdateUserMutationInput([UpdateUserMutationInput::EMAIL_NOT_NULL]);

        self::assertEquals([UpdateUserMutationInput::EMAIL_NOT_NULL], $input->getValidationGroups());
    }
}
