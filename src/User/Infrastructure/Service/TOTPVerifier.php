<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Contract\TOTPVerifierInterface;
use OTPHP\TOTP;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class TOTPVerifier implements TOTPVerifierInterface
{
    #[\Override]
    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): bool {
        try {
            $totp = TOTP::create($secret);
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
