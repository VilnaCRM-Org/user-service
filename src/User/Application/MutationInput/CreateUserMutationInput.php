<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateUserMutationInput implements MutationInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Email]
        public ?string $initials = null,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $password = null,
    )
    {
    }

    public function getValidationGroups(): array
    {
        return [];
    }
}