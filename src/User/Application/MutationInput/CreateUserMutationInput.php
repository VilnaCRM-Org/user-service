<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\Password;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserMutationInput implements MutationInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Email]
        public ?string $email = null,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Initials]
        public ?string $initials = null,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Password]
        public ?string $password = null,
    ) {
    }
}
