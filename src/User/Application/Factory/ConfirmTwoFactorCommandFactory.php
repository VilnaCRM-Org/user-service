<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\ConfirmTwoFactorCommand;

final class ConfirmTwoFactorCommandFactory implements ConfirmTwoFactorCommandFactoryInterface
{
    #[\Override]
    public function create(
        string $userEmail,
        string $twoFactorCode,
        string $currentSessionId,
    ): ConfirmTwoFactorCommand {
        return new ConfirmTwoFactorCommand($userEmail, $twoFactorCode, $currentSessionId);
    }
}
