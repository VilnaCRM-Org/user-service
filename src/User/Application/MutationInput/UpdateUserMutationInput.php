<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserMutationInput implements MutationInput
{
    public const INITIALS_NOT_NULL = 'initials_not_null';
    public const EMAIL_NOT_NULL = 'email_not_null';
    public const NEW_PASSWORD_NOT_NULL = 'new_password_not_null';

    /**
     * @param array<string> $validationGroups
     */
    public function __construct(
        private array $validationGroups,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $password = null,
        #[Assert\NotBlank(groups: [self::INITIALS_NOT_NULL])]
        #[Assert\Length(max: 255, groups: [self::INITIALS_NOT_NULL])]
        public ?string $initials = null,
        #[Assert\Email(groups: [self::EMAIL_NOT_NULL])]
        #[Assert\NotBlank(groups: [self::EMAIL_NOT_NULL])]
        #[Assert\Length(max: 255, groups: [self::EMAIL_NOT_NULL])]
        public ?string $email = null,
        #[Assert\NotBlank(groups: [self::NEW_PASSWORD_NOT_NULL])]
        #[Assert\Length(max: 255, groups: [self::NEW_PASSWORD_NOT_NULL])]
        public ?string $newPassword = null,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getValidationGroups(): array
    {
        return $this->validationGroups;
    }
}
