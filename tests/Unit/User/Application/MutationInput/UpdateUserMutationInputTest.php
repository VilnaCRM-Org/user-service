<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\TestValidationUtils;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\UpdateUserMutationInput;

class UpdateUserMutationInputTest extends UnitTestCase
{
    private TestValidationUtils $validationUtils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationUtils = new TestValidationUtils();
    }

    public function testConstructWithAllDataAndNoValidationGroups(): void
    {
        $password = $this->faker->password();
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $newPassword = $this->faker->password();

        $input = new UpdateUserMutationInput($password, $initials, $email, $newPassword);

        $this->assertEquals($password, $input->password);
        $this->assertEquals($initials, $input->initials);
        $this->assertEquals($email, $input->email);
        $this->assertEquals($newPassword, $input->newPassword);
    }

    public function testMaxEmailLength(): void
    {
        $email = $this->validationUtils->addCharToBeginning($this->faker->email(), 256, 'a');
        $dto = new UpdateUserMutationInput(
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->getValidInitials(),
            $email,
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testMaxInitialsLength(): void
    {
        $dto = new UpdateUserMutationInput(
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->addCharToBeginning($this->validationUtils->getValidInitials(), 256, 'a'),
            $this->faker->email(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('initials', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testOptionalInitials(): void
    {
        $dto = new UpdateUserMutationInput(
            $this->validationUtils->getValidPassword(),
            '',
            $this->faker->email(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(0, $errors);
    }

    public function testOptionalPassword(): void
    {
        $dto = new UpdateUserMutationInput(
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->getValidInitials(),
            $this->faker->email(),
            '',
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(0, $errors);
    }
}
