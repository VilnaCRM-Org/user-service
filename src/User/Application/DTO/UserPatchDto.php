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
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        #[Initials(optional: true)]
        public ?string $initials = null,
        #[Assert\NotBlank]
        #[Password]
        public ?string $oldPassword = null,
        #[Password(optional: true)]
        public ?string $newPassword = null,
    ) {
    }
}
