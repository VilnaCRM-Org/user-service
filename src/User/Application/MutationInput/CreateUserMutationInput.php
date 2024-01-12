<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserMutationInput implements MutationInput
{
    public function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'initials' => [
                new Assert\NotBlank(),
                new Assert\Length(max: 255),
                ],
            'email' => [
                new Assert\NotBlank(),
                new Assert\Email(),
                new Assert\Length(max: 255),
            ],
            'password' => [
                new Assert\NotBlank(),
                new Assert\Length(max: 255),
            ],
        ]);
    }
}
