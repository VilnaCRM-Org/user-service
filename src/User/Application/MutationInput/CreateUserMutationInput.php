<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

final readonly class CreateUserMutationInput implements MutationInput
{
    public function __construct(
        public ?string $email = null,
        public ?string $initials = null,
        public ?string $password = null,
    ) {
    }
}
