<?php

declare(strict_types=1);

namespace App\User\Application\Processor\Revoker;

interface AllSessionsRevokerInterface
{
    public function revokeAllSessions(string $userId, string $reason): int;
}
