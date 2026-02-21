<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;

interface UserAuthenticationServiceInterface
{
    /**
     * Validates credentials, records failures, publishes events, and enforces
     * account lockout. Returns the authenticated User on success.
     *
     * Throws LockedHttpException when the account is locked.
     * Throws UnauthorizedHttpException when credentials are invalid.
     */
    public function authenticate(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent
    ): User;
}
