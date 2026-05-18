<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class RegisterUserBatchCommand implements CommandInterface
{
    /**
     * @param list<array{email: string, initials: string, password: string}> $users
     */
    public function __construct(
        public readonly array $users,
    ) {
    }
}
