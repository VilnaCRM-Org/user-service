<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ConfirmUserDto
{
    #[Assert\NotBlank]
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }
}
