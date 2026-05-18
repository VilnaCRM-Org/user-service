<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;

final readonly class RegisterUserBatchCommandFactory implements
    RegisterUserBatchCommandFactoryInterface
{
    /**
     * @param list<array{email: string, initials: string, password: string}> $users
     */
    #[\Override]
    public function create(
        array $users
    ): RegisterUserBatchCommand {
        return new RegisterUserBatchCommand($users);
    }
}
