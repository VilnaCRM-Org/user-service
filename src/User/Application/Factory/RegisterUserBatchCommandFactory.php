<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use Doctrine\Common\Collections\ArrayCollection;

final readonly class RegisterUserBatchCommandFactory implements
    RegisterUserBatchCommandFactoryInterface
{
    public function create(
        ArrayCollection $users
    ): RegisterUserBatchCommand {
        return new RegisterUserBatchCommand($users);
    }
}
