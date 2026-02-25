<?php

declare(strict_types=1);

namespace App\User\Application\Component;

use App\User\Domain\Entity\User;

interface TwoFactorCodeVerifierInterface
{
    public function verifyTotpOrFail(User $user, string $code): void;

    public function verifyAndConsumeOrFail(User $user, string $code): void;

    public function resolveVerificationMethod(User $user, string $code): ?string;

    public function countRemainingCodes(string $userId): int;
}
