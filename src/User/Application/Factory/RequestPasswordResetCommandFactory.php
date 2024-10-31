<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RequestPasswordResetCommand;

final class RequestPasswordResetCommandFactory implements
    RequestPasswordResetCommandFactoryInterface
{
    public function create(string $email): RequestPasswordResetCommand
    {
        return new RequestPasswordResetCommand($email);
    }
}
