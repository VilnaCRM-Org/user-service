<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\Password;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserPatchDto
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email,
        #[Assert\Length(max: 255)]
        #[Initials(optional: true)]
        public string $initials,
        #[Assert\NotBlank]
        #[Password]
        public string $oldPassword,
        #[Password(optional: true)]
        public string $newPassword,
    ) {
    }
}
