<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

final readonly class UpdateUserCommand implements CommandInterface
{
    public function __construct(
        public UserInterface $user,
        public UserUpdate $updateData,
    ) {
    }
}
