<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RequestPasswordResetDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 254)]
        public string $email = ''
    ) {
    }
}
