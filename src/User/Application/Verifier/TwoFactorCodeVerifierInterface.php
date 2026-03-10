<?php

declare(strict_types=1);

namespace App\User\Application\Verifier;

use App\User\Domain\Entity\User;

interface TwoFactorCodeVerifierInterface
{
    public const METHOD_TOTP = 'totp';
    public const METHOD_RECOVERY_CODE = 'recovery_code';

    public function verifyAndConsumeOrFail(User $user, string $code): void;

    /**
     * Verifies the code and, if it is a recovery code, also consumes it.
     * Returns the verification method name on success, null on failure.
     */
    public function verifyAndResolveMethod(User $user, string $code): ?string;

    public function countRemainingCodes(string $userId): int;
}
