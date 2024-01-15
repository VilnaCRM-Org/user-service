<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserPatchDto
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email,

        #[Assert\Length(max: 255)]
        public string $initials,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $oldPassword,

        #[Assert\Length(max: 255)]
        public string $newPassword,
    )
    {
    }
}
