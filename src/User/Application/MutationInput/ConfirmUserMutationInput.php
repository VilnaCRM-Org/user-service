<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

class ConfirmUserMutationInput implements MutationInput
{
    public function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'token' => new Assert\NotBlank(),
        ]);
    }
}
