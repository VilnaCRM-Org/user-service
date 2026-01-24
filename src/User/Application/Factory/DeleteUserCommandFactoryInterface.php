<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\DeleteUserCommand;
use App\User\Domain\Entity\UserInterface;

interface DeleteUserCommandFactoryInterface
{
    public function create(UserInterface $user): DeleteUserCommand;
}
