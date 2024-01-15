<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ConfirmUserDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $token
    )
    {
    }
}
