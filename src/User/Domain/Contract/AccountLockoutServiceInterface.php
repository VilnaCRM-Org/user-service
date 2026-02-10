<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

interface AccountLockoutServiceInterface
{
    public function isLocked(string $email): bool;

    public function recordFailure(string $email): bool;

    public function clearFailures(string $email): void;
}
