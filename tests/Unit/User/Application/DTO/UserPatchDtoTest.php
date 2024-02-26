<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\TestValidationUtils;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;

class UserPatchDtoTest extends UnitTestCase
{
    private TestValidationUtils $validationUtils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationUtils = new TestValidationUtils();
    }

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

    public function testMaxEmailLength(): void
    {
        $email = $this->validationUtils->addCharToBeginning($this->faker->email());
        $dto = new UserPatchDto(
            $email,
            $this->validationUtils->getValidInitials(),
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testMaxInitialsLength(): void
    {
        $dto = new UserPatchDto(
            $this->faker->email(),
            $this->validationUtils->addCharToBeginning($this->validationUtils->getValidInitials()),
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('initials', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testOptionalInitials(): void
    {
        $dto = new UserPatchDto(
            $this->faker->email(),
            '',
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(0, $errors);
    }

    public function testOptionalPassword(): void
    {
        $dto = new UserPatchDto(
            $this->faker->email(),
            $this->validationUtils->getValidInitials(),
            $this->validationUtils->getValidPassword(),
            '',
        );

        $errors = $this->validationUtils->validator->validate($dto);

        $this->assertCount(0, $errors);
    }
}
