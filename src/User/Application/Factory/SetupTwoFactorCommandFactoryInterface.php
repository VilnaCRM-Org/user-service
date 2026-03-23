<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SetupTwoFactorCommand;

interface SetupTwoFactorCommandFactoryInterface
{
    public function create(string $userEmail): SetupTwoFactorCommand;
}
