<?php

declare(strict_types=1);

namespace App\User\Infrastructure\TwoFactor;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;

final class TotpCreator implements TOTPCreatorInterface
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const SECRET_LENGTH = 32;
    private const DEFAULT_PERIOD = 30;
    private const DEFAULT_DIGEST = 'sha1';
    private const DEFAULT_DIGITS = 6;
    private const DEFAULT_EPOCH = 0;

    #[\Override]
    public function create(?string $secret = null): TOTPInterface
    {
        $totp = new TOTP($secret ?? $this->generateSecret());
        $totp->setPeriod(self::DEFAULT_PERIOD);
        $totp->setDigest(self::DEFAULT_DIGEST);
        $totp->setDigits(self::DEFAULT_DIGITS);
        $totp->setEpoch(self::DEFAULT_EPOCH);

        return $totp;
    }

    private function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }

        return $secret;
    }
}
