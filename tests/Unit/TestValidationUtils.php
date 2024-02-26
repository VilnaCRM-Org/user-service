<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestValidationUtils
{
    public Generator $faker;
    public ValidatorInterface $validator;
    public function __construct()
    {
        $this->faker = Factory::create();
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
    }

    public function addCharToBeginning(
        string $string,
        int $length = 256,
        string $char = 'a'
    ): string {
        if (strlen($string) >= $length) {
            return $string;
        }

        $charsToAdd = $length - strlen($string);

        for ($i = 0; $i < $charsToAdd; $i++) {
            $string = $char . $string;
        }

        return $string;
    }

    public function getValidPassword(): string
    {
        return $this->faker->password(minLength: 8) .
            $this->faker->numberBetween(1, 9) . 'A';
    }

    public function getValidInitials(): string
    {
        return $this->faker->firstName() . ' ' . $this->faker->lastName();
    }
}
