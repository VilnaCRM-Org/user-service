<?php

declare(strict_types=1);

namespace App\User\Application\DTO\Email;

use Symfony\Component\Validator\Constraints as Assert;

class RetryMutationDto
{
    #[Assert\NotBlank]
    public string $userId = '';
}
