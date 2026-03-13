<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

interface AccountLockoutGuardInterface
{
    public const MAX_ATTEMPTS = 20;
    public const LOCKOUT_SECONDS = 900;

    public function isLocked(string $email): bool;

    public function recordFailure(string $email): bool;

    public function clearFailures(string $email): void;

    public function maxAttempts(): int;

    public function lockoutSeconds(): int;
}
