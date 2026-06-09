<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Validator;

use App\User\Application\Validator\TOTPValidatorInterface;
use OTPHP\TOTP;

final class TOTPValidator implements TOTPValidatorInterface
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
        return $this->resolveAcceptedTimestep($secret, $code, $timestamp) !== null;
    }

    /**
     * Accepts the current and the previous time window only (RFC 6238 §5.2 clock
     * skew tolerance). The future window is intentionally rejected to keep the
     * replay/acceptance surface minimal.
     *
     * @psalm-return int<0, max>|null
     */
    #[\Override]
    public function resolveAcceptedTimestep(
        string $secret,
        string $code,
        ?int $timestamp = null
    ): ?int {
        try {
            $totp = $this->createTotp($secret);
            $pointInTime = $timestamp ?? time();
            $period = $totp->getPeriod();

            if ($totp->verify($code, $pointInTime)) {
                return $this->timestepAt($pointInTime, $period);
            }

            $previousWindowTimestamp = $pointInTime - $period;
            if ($previousWindowTimestamp >= 0 && $totp->verify($code, $previousWindowTimestamp)) {
                return $this->timestepAt($previousWindowTimestamp, $period);
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function createTotp(string $secret): TOTP
    {
        $totp = new TOTP($secret);
        $totp->setPeriod(self::DEFAULT_PERIOD);
        $totp->setDigest(self::DEFAULT_DIGEST);
        $totp->setDigits(self::DEFAULT_DIGITS);
        $totp->setEpoch(self::DEFAULT_EPOCH);

        return $totp;
    }

    /**
     * @psalm-return int<0, max>
     */
    private function timestepAt(int $timestamp, int $period): int
    {
        return (int) max(0, intdiv($timestamp - self::DEFAULT_EPOCH, $period));
    }
}
