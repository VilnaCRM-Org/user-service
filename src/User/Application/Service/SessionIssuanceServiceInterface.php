<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;
use DateTimeImmutable;

interface SessionIssuanceServiceInterface
{
    /**
     * Creates an authenticated session, issues a refresh token, and generates
     * a JWT access token. Persists both the session and the refresh token.
     */
    public function issue(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): IssuedSession;
}
