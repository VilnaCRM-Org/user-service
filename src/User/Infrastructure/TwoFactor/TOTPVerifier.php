<?php

declare(strict_types=1);

namespace App\User\Infrastructure\TwoFactor;

use App\User\Application\Verifier\TOTPVerifierInterface;

final class TOTPVerifier implements TOTPVerifierInterface
{
    public function __construct(private readonly TOTPCreatorInterface $totpCreator)
    {
    }

    #[\Override]
    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): bool {
        try {
            $totp = $this->totpCreator->create($secret);
            $pointInTime = $timestamp ?? time();
            $period = $totp->getPeriod();
            $previousWindowTimestamp = $pointInTime - $period;

            return $totp->verify($code, $pointInTime)
                || ($previousWindowTimestamp >= 0
                    && $totp->verify($code, $previousWindowTimestamp))
                || $totp->verify($code, $pointInTime + $period);
        } catch (\Throwable) {
            return false;
        }
    }
}
