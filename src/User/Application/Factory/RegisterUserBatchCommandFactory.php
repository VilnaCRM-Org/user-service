<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Domain\Collection\UserCollection;

final readonly class RegisterUserBatchCommandFactory implements
    RegisterUserBatchCommandFactoryInterface
{
    public function create(
        UserCollection $users
    ): RegisterUserBatchCommand {
        return new RegisterUserBatchCommand($users);
    }
}
