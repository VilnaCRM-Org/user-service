<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RequestPasswordResetCommand;

interface RequestPasswordResetCommandFactoryInterface
{
    public function create(string $email): RequestPasswordResetCommand;
}
