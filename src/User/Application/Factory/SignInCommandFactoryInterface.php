<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\SignInCommand;

interface SignInCommandFactoryInterface
{
    public function create(
        string $email,
        string $password,
        bool $rememberMe,
        string $ipAddress,
        string $userAgent,
    ): SignInCommand;
}
