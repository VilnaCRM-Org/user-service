<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MutationInputValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function validate(array $contextArgs, MutationInput $input): void
    {
        $errors = $this->validator->validate($contextArgs, $input->getConstraints());

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
