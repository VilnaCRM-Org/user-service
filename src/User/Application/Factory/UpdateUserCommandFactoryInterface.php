<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

interface UpdateUserCommandFactoryInterface
{
    public function create(
        UserInterface $user,
        UserUpdate $updateData
    ): UpdateUserCommand;
}
