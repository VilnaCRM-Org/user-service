<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\ConfirmTwoFactorCommand;

interface ConfirmTwoFactorCommandFactoryInterface
{
    public function create(
        string $userEmail,
        string $twoFactorCode,
        string $currentSessionId,
    ): ConfirmTwoFactorCommand;
}
