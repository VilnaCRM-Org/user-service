<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\CreateUserMutationInput;

final class CreateUserMutationInputTest extends UnitTestCase
{
    public function testConstructWithValidData(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $input = new CreateUserMutationInput($email, $initials, $password);

        $this->assertEquals($email, $input->email);
        $this->assertEquals($initials, $input->initials);
        $this->assertEquals($password, $input->password);
    }

    public function testConstructWithNullFields(): void
    {
        $input = new CreateUserMutationInput();

        $this->assertNull($input->email);
        $this->assertNull($input->initials);
        $this->assertNull($input->password);
    }
}
