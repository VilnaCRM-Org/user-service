<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

final class UpdateUserCommandFactory implements
    UpdateUserCommandFactoryInterface
{
    #[\Override]
    public function create(
        UserInterface $user,
        UserUpdate $updateData
    ): UpdateUserCommand {
        return new UpdateUserCommand($user, $updateData);
    }
}
