<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\UpdateUserMutationInput;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateUserMutationInputTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
    }

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

    public function testMaxEmailLength(): void
    {
        $email = $this->addCharToBeginning($this->faker->email(), 256, 'a');
        $dto = new UpdateUserMutationInput(
            [
                UpdateUserMutationInput::EMAIL_NOT_NULL,
                UpdateUserMutationInput::INITIALS_NOT_NULL,
                UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL
            ],
            $this->getValidPassword(),
            $this->getValidInitials(),
            $email,
            $this->getValidPassword(),
        );

        $errors = $this->validator->validate($dto, null, $dto->getValidationGroups());

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testMaxInitialsLength(): void
    {
        $dto = new UpdateUserMutationInput(
            [
                UpdateUserMutationInput::EMAIL_NOT_NULL,
                UpdateUserMutationInput::INITIALS_NOT_NULL,
                UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL
            ],
            $this->getValidPassword(),
            $this->addCharToBeginning($this->getValidInitials(), 256, 'a'),
            $this->faker->email(),
            $this->getValidPassword(),
        );

        $errors = $this->validator->validate($dto, null, $dto->getValidationGroups());

        $this->assertCount(1, $errors);
        $this->assertSame('initials', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    private function addCharToBeginning(string $string, int $length, string $char): string
    {
        if (strlen($string) >= $length) {
            return $string;
        }

        $charsToAdd = $length - strlen($string);

        for ($i = 0; $i < $charsToAdd; $i++) {
            $string = $char . $string;
        }

        return $string;
    }

    private function getValidPassword(): string
    {
        return $this->faker->password(minLength: 8) .
            $this->faker->numberBetween(1, 9) . 'A';
    }

    private function getValidInitials(): string
    {
        return $this->faker->firstName() . ' ' . $this->faker->lastName();
    }
}
