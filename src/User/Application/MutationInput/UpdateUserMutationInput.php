<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\Password;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserMutationInput implements MutationInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Password]
        public ?string $password = null,
        #[Assert\Length(max: 255)]
        #[Initials(optional: true)]
        public ?string $initials = null,
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Password(optional: true)]
        public ?string $newPassword = null,
    ) {
    }
}
