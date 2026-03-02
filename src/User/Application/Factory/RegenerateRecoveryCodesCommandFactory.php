<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegenerateRecoveryCodesCommand;

final class RegenerateRecoveryCodesCommandFactory implements
    RegenerateRecoveryCodesCommandFactoryInterface
{
    #[\Override]
    public function create(
        string $userEmail,
        string $currentSessionId,
    ): RegenerateRecoveryCodesCommand {
        return new RegenerateRecoveryCodesCommand($userEmail, $currentSessionId);
    }
}
