<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use App\Shared\Application\Validator\OptionalEmail;
use App\Shared\Application\Validator\OptionalInitials;
use App\Shared\Application\Validator\OptionalPassword;
use App\Shared\Application\Validator\Password;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserMutationInput implements MutationInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Password]
        public ?string $password = null,
        #[Assert\Length(max: 255)]
        #[OptionalInitials]
        public ?string $initials = null,
        #[OptionalEmail]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[OptionalPassword]
        public ?string $newPassword = null,
    ) {
    }
}
