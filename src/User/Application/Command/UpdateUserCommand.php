<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserUpdateData;

final readonly class UpdateUserCommand implements CommandInterface
{
    public function __construct(
        public User $user,
        public UserUpdateData $updateData,
    ) {
    }
}