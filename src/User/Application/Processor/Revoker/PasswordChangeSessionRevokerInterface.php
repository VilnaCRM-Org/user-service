<?php

declare(strict_types=1);

namespace App\User\Application\Processor\Revoker;

interface PasswordChangeSessionRevokerInterface
{
    public function revokeOtherSessions(string $userId, string $currentSessionId): int;
}
