<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use OTPHP\TOTP;

final class TOTPFactory implements TOTPFactoryInterface
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const SECRET_LENGTH = 32;
    private const DEFAULT_PERIOD = 30;
    private const DEFAULT_EPOCH = 0;

    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    #[\Override]
    public function create(string $label, string $issuer): array
    {
        $totp = new TOTP($this->generateSecret());
        $totp->setPeriod(self::DEFAULT_PERIOD);
        $totp->setEpoch(self::DEFAULT_EPOCH);
        $totp->setLabel($label);
        $totp->setIssuer($issuer);

        return [
            'secret' => $totp->getSecret(),
            'otpauth_uri' => $totp->getProvisioningUri(),
        ];
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
