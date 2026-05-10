<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class MutationInputValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function validate(object $input): void
    {
        $errors = $this->validator->validate(
            $input,
        );

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
