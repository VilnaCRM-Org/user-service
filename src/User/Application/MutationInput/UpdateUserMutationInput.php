<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateUserMutationInput implements MutationInput
{
    public function __construct(
        private array $validationGroups,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $password = null,
        #[Assert\NotBlank(groups: ['initials_not_null'])]
        #[Assert\Length(max: 255, groups: ['initials_not_null'])]
        public ?string $initials = null,
        #[Assert\Email(groups: ['email_not_null'])]
        #[Assert\NotBlank(groups: ['email_not_null'])]
        #[Assert\Length(max: 255, groups: ['email_not_null'])]
        public ?string $email = null,
        #[Assert\NotBlank(groups: ['new_password_not_null'])]
        #[Assert\Length(max: 255, groups: ['new_password_not_null'])]
        public ?string $newPassword = null,
    ) {
    }

    public function getValidationGroups(): array
    {
        return $this->validationGroups;
    }
}
