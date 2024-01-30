<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserUpdateData;

interface UpdateUserCommandFactoryInterface
{
    public function create(
        User $user,
        UserUpdateData $updateData
    ): UpdateUserCommand;
}
