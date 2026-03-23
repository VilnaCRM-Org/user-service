<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegenerateRecoveryCodesCommand;

interface RegenerateRecoveryCodesCommandFactoryInterface
{
    public function create(
        string $userEmail,
        string $currentSessionId,
    ): RegenerateRecoveryCodesCommand;
}
