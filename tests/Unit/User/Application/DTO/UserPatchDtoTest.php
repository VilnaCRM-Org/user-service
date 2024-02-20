<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserPatchDtoTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
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
        $email = $this->addCharToBeginning($this->faker->email(), 256, 'a');
        $dto = new UserPatchDto(
            $email,
            $this->getValidInitials(),
            $this->getValidPassword(),
            $this->getValidPassword(),
        );

        $errors = $this->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testMaxInitialsLength(): void
    {
        $dto = new UserPatchDto(
            $this->faker->email(),
            $this->addCharToBeginning($this->getValidInitials(), 256, 'a'),
            $this->getValidPassword(),
            $this->getValidPassword(),
        );

        $errors = $this->validator->validate($dto);

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
