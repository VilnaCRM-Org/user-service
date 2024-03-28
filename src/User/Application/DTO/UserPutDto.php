<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserPutDto
{
    public function __construct(
        public ?string $email = null,
        public ?string $initials = null,
        public ?string $oldPassword = null,
        public ?string $newPassword = null,
    ) {
    }
}
