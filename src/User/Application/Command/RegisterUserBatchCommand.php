<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Application\DTO\BatchUserRegistrationInputCollection;

final readonly class RegisterUserBatchCommand implements CommandInterface
{
    public function __construct(
        public BatchUserRegistrationInputCollection $users,
    ) {
    }
}
