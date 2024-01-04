<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Domain\Entity\User;

readonly class UpdateUserCommand implements Command
{
    public function __construct(
        public User $user,
        public string $newEmail,
        public string $newInitials,
        public string $newPassword,
        public string $oldPassword,
    ) {
    }
}
