<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;

interface TwoFactorCodeVerifierInterface
{
    /**
     * Verifies a TOTP code against the user's stored secret.
     * Throws UnauthorizedHttpException on failure.
     */
    public function verifyTotpOrFail(User $user, string $code): void;

    /**
     * Verifies a TOTP or recovery code. Marks the recovery code as used if applicable.
     * Throws UnauthorizedHttpException on failure.
     */
    public function verifyAndConsumeOrFail(User $user, string $code): void;

    /**
     * Resolves which verification method was used ('totp' or 'recovery_code').
     * Marks the recovery code as used if applicable.
     * Returns null if the code is invalid.
     */
    public function resolveVerificationMethod(User $user, string $code): ?string;

    /**
     * Counts the number of unused recovery codes for the user.
     *
     * @psalm-return int<0, max>
     */
    public function countRemainingCodes(string $userId): int;
}
