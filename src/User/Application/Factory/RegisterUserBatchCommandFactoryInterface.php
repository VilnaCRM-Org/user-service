<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\UserRegisterBatchDto;

interface RegisterUserBatchCommandFactoryInterface
{
    public function create(
        UserRegisterBatchDto $batch
    ): RegisterUserBatchCommand;
}
