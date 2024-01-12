<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserMutationInput implements MutationInput
{
    private Assert\Collection $constraints;

    public function __construct(array $contextArgs)
    {
        $fields = [
            'password' => [
            new Assert\NotBlank(),
            new Assert\Length(max: 255), ],
            'id' => new Assert\NotBlank(),
        ];
        if (array_key_exists('initials', $contextArgs)) {
            $fields['initials'] = [
                new Assert\NotBlank(),
                new Assert\Length(max: 255),
            ];
        }
        if (array_key_exists('email', $contextArgs)) {
            $fields['email'] = [
                new Assert\NotBlank(),
                new Assert\Email(),
                new Assert\Length(max: 255),
            ];
        }
        if (array_key_exists('newPassword', $contextArgs)) {
            $fields['newPassword'] = [
                new Assert\NotBlank(),
                new Assert\Length(max: 255),
            ];
        }

        $this->constraints = new Assert\Collection($fields);
    }

    public function getConstraints(): Assert\Collection
    {
        return $this->constraints;
    }
}
