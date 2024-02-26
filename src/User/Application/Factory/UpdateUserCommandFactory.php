<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserUpdate;

final class UpdateUserCommandFactory implements
    UpdateUserCommandFactoryInterface
{
    public function create(
        User $user,
        UserUpdate $updateData
    ): UpdateUserCommand {
        return new UpdateUserCommand($user, $updateData);
    }
}
