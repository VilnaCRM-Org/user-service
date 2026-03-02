<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CompleteTwoFactorCommand;

final class CompleteTwoFactorCommandFactory implements CompleteTwoFactorCommandFactoryInterface
{
    #[\Override]
    public function create(
        string $pendingSessionId,
        string $twoFactorCode,
        string $ipAddress,
        string $userAgent,
    ): CompleteTwoFactorCommand {
        return new CompleteTwoFactorCommand(
            $pendingSessionId,
            $twoFactorCode,
            $ipAddress,
            $userAgent
        );
    }
}
