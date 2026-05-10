<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\DisableTwoFactorCommand;

interface DisableTwoFactorCommandFactoryInterface
{
    public function create(
        string $userEmail,
        string $twoFactorCode,
    ): DisableTwoFactorCommand;
}
