<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CompleteTwoFactorCommand;

interface CompleteTwoFactorCommandFactoryInterface
{
    public function create(
        string $pendingSessionId,
        string $twoFactorCode,
        string $ipAddress,
        string $userAgent,
    ): CompleteTwoFactorCommand;
}
