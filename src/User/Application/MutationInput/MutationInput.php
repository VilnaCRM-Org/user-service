<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

interface MutationInput
{
    public function getConstraints(): Assert\Collection;
}
