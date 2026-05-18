<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;

interface RegisterUserBatchCommandFactoryInterface
{
    /**
     * @param list<array{email: string, initials: string, password: string}> $users
     */
    public function create(
        array $users
    ): RegisterUserBatchCommand;
}
