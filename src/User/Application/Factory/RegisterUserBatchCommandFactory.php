<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Domain\ValueObject\UserBatch;

final readonly class RegisterUserBatchCommandFactory implements
    RegisterUserBatchCommandFactoryInterface
{
    public function create(
        UserBatch $userBatch
    ): RegisterUserBatchCommand {
        return new RegisterUserBatchCommand($userBatch);
    }
}
