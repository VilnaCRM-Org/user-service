<?php

declare(strict_types=1);

namespace App\User\Infrastructure\TwoFactor;

use App\User\Application\Validator\Verifier\TOTPVerifierInterface;
use OTPHP\TOTP;

final class TOTPVerifier implements TOTPVerifierInterface
{
    private const DEFAULT_PERIOD = 30;
    private const DEFAULT_DIGEST = 'sha1';
    private const DEFAULT_DIGITS = 6;
    private const DEFAULT_EPOCH = 0;

    #[\Override]
    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): bool {
        try {
            $totp = new TOTP($secret);
            $totp->setPeriod(self::DEFAULT_PERIOD);
            $totp->setDigest(self::DEFAULT_DIGEST);
            $totp->setDigits(self::DEFAULT_DIGITS);
            $totp->setEpoch(self::DEFAULT_EPOCH);

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
