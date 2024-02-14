<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\UpdateUserMutationInput;
use App\User\Application\Transformer\UpdateUserMutationInputTransformer;
use Faker\Factory;

class UpdateUserMutationInputTransformerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }

    public function testTransformWithAllFields(): void
    {
        $password = $this->faker->password();
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $newPassword = $this->faker->password();

        $args = [
            'email' => $email,
            'password' => $password,
            'initials' => $initials,
            'newPassword' => $newPassword,
        ];

        $transformer = new UpdateUserMutationInputTransformer();
        $input = $transformer->transform($args);

        $this->assertInstanceOf(UpdateUserMutationInput::class, $input);
        $this->assertEquals(
            [
                UpdateUserMutationInput::INITIALS_NOT_NULL,
                UpdateUserMutationInput::EMAIL_NOT_NULL,
                UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL,
            ],
            $input->getValidationGroups()
        );
        $this->assertEquals($password, $input->password);
        $this->assertEquals($initials, $input->initials);
        $this->assertEquals($email, $input->email);
        $this->assertEquals($newPassword, $input->newPassword);
    }

    public function testTransformWithMissingFields(): void
    {
        $email = $this->faker->email();
        $newPassword = $this->faker->password();

        $args = [
            'email' => $email,
            'newPassword' => $newPassword,
        ];

        $transformer = new UpdateUserMutationInputTransformer();
        $input = $transformer->transform($args);

        $this->assertInstanceOf(UpdateUserMutationInput::class, $input);
        $this->assertEquals(
            [
                UpdateUserMutationInput::EMAIL_NOT_NULL,
                UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL
            ],
            $input->getValidationGroups()
        );
        $this->assertNull($input->password);
        $this->assertNull($input->initials);
        $this->assertEquals($email, $input->email);
        $this->assertEquals($newPassword, $input->newPassword);
    }

    public function testTransformWithEmptyStrings(): void
    {
        $transformer = new UpdateUserMutationInputTransformer();
        $input = $transformer->transform([]);

        $this->assertInstanceOf(UpdateUserMutationInput::class, $input);
        $this->assertEquals([], $input->getValidationGroups());
        $this->assertNull($input->password);
        $this->assertNull($input->initials);
        $this->assertNull($input->email);
        $this->assertNull($input->newPassword);
    }
}
