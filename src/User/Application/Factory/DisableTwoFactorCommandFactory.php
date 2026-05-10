<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\DisableTwoFactorCommand;

final class DisableTwoFactorCommandFactory implements DisableTwoFactorCommandFactoryInterface
{
    #[\Override]
    public function create(
        string $userEmail,
        string $twoFactorCode,
    ): DisableTwoFactorCommand {
        return new DisableTwoFactorCommand($userEmail, $twoFactorCode);
    }
}
