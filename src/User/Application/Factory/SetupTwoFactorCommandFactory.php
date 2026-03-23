<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SetupTwoFactorCommand;

final class SetupTwoFactorCommandFactory implements SetupTwoFactorCommandFactoryInterface
{
    #[\Override]
    public function create(string $userEmail): SetupTwoFactorCommand
    {
        return new SetupTwoFactorCommand($userEmail);
    }
}
