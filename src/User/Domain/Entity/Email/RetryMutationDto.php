<?php

namespace App\User\Domain\Entity\Email;

use Symfony\Component\Validator\Constraints as Assert;

class RetryMutationDto
{
    #[Assert\NotBlank]
    public string $userId = '';
}
