<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\ConfirmUserCommand;
use App\User\Domain\Entity\ConfirmationToken;

final class ConfirmUserCommandFactory implements
    ConfirmUserCommandFactoryInterface
{
    #[\Override]
    public function create(ConfirmationToken $token): ConfirmUserCommand
    {
        return new ConfirmUserCommand($token);
    }
}
