<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

final readonly class UpdateUserMutationInput implements MutationInput
{
    public function __construct(
        public ?string $password = null,
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $newPassword = null,
    ) {
    }
}
