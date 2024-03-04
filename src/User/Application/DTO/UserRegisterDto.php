<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\Password;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserRegisterDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Initials]
        public ?string $initials = null,
        #[Assert\NotBlank]
        #[Password]
        public ?string $password = null,
    ) {
    }
}
