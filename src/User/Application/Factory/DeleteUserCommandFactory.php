<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\DeleteUserCommand;
use App\User\Domain\Entity\UserInterface;

final class DeleteUserCommandFactory implements DeleteUserCommandFactoryInterface
{
    #[\Override]
    public function create(UserInterface $user): DeleteUserCommand
    {
        return new DeleteUserCommand($user);
    }
}
