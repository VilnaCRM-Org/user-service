<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Collection\UserCollection;

final class RegisterUserBatchCommand implements CommandInterface
{
    public function __construct(
        public readonly UserCollection $users,
    ) {
    }
}
