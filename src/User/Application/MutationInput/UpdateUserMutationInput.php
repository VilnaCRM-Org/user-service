<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserMutationInput implements MutationInput
{
    private Assert\Collection $constraints;

    public function __construct(array $contextArgs)
    {
        $fields = ['password' => new Assert\NotBlank(), 'id' => new Assert\NotBlank()];
        if (array_key_exists('initials', $contextArgs)) {
            $fields['initials'] = new Assert\NotBlank();
        }
        if (array_key_exists('email', $contextArgs)) {
            $fields['email'] = [
                new Assert\NotBlank(),
                new Assert\Email(),
            ];
        }
        if (array_key_exists('newPassword', $contextArgs)) {
            $fields['newPassword'] = new Assert\NotBlank();
        }

        $this->constraints = new Assert\Collection($fields);
    }

    public function getConstraints(): Assert\Collection
    {
        return $this->constraints;
    }
}
