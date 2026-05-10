<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\User\Application\Command\RequestPasswordResetCommand;

interface RequestPasswordResetHandlerInterface
{
    public function __invoke(RequestPasswordResetCommand $command): void;
}
