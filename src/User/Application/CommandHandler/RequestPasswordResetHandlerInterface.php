<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\RequestPasswordResetCommandResponse;

interface RequestPasswordResetHandlerInterface
{
    public function __invoke(
        RequestPasswordResetCommand $command
    ): RequestPasswordResetCommandResponse;
}
