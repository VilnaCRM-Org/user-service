<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\TestValidationUtils;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\CreateUserMutationInput;

class CreateUserMutationInputTest extends UnitTestCase
{
    private TestValidationUtils $validationUtils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationUtils = new TestValidationUtils();
    }

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

    public function testMaxEmailLength(): void
    {
        $email = $this->validationUtils->addCharToBeginning($this->faker->email(), 256, 'a');
        $dto = new CreateUserMutationInput(
            $email,
            $this->validationUtils->getValidInitials(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testMaxInitialsLength(): void
    {
        $dto = new CreateUserMutationInput(
            $this->faker->email(),
            $this->validationUtils->addCharToBeginning($this->validationUtils->getValidInitials(), 256, 'a'),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('initials', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }
}
