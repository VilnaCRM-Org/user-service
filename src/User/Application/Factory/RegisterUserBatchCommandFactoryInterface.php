<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Domain\Collection\UserCollection;

interface RegisterUserBatchCommandFactoryInterface
{
    public function create(
        UserCollection $users
    ): RegisterUserBatchCommand;
}
