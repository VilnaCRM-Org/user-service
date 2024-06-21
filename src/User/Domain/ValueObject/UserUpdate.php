<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final readonly class UserUpdate
{
    public function __construct(
        public string $newEmail,
        public string $newInitials,
        public string $newPassword,
        public string $oldPassword,
    ) {
    }
}
