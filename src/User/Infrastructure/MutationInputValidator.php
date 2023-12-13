<?php

namespace App\User\Infrastructure;

use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MutationInputValidator
{
    public function __construct(
        private ValidatorInterface $validator)
    {
    }

    public function validate(object $item)
    {
        $errors = $this->validator->validate($item);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
