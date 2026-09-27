<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

interface TOTPValidatorInterface
{
    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): bool;

    /**
     * Verifies the code and, on success, returns the matched TOTP time-step
     * counter (used for replay detection). Returns null when the code is invalid.
     *
     * @psalm-return int<0, max>|null
     */
    public function resolveAcceptedTimestep(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): ?int;
}
