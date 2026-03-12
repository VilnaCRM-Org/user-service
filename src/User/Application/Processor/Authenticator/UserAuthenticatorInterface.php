<?php

declare(strict_types=1);

namespace App\User\Application\Processor\Authenticator;

use App\User\Domain\Entity\User;

interface UserAuthenticatorInterface
{
    public function authenticate(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent
    ): User;
}
