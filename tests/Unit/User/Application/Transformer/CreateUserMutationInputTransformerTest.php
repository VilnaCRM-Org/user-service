<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\CreateUserMutationInput;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;

class CreateUserMutationInputTransformerTest extends UnitTestCase
{
    public function testTransform(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $transformer = new CreateUserMutationInputTransformer();
        $args = [
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ];

        $input = $transformer->transform($args);

        $this->assertInstanceOf(CreateUserMutationInput::class, $input);
        $this->assertSame($email, $input->email);
        $this->assertSame($initials, $input->initials);
        $this->assertSame($password, $input->password);
    }
}
