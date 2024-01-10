<?php

declare(strict_types=1);

namespace App\User\Application\DTO\Token;

use Symfony\Component\Validator\Constraints as Assert;

class ConfirmUserDto
{
    #[Assert\NotBlank]
    public string $token;
}
