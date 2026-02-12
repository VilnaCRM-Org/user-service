<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Contract\TOTPSecretGeneratorInterface;
use OTPHP\TOTP;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class TOTPSecretGenerator implements TOTPSecretGeneratorInterface
{
    private const OTP_ISSUER = 'VilnaCRM';

    /**
     * @return string[]
     *
     * @psalm-return array{secret: string, otpauth_uri: string}
     */
    #[\Override]
    public function generate(string $email): array
    {
        $totp = TOTP::create();
        $totp->setIssuer(self::OTP_ISSUER);
        $totp->setLabel($email);

        return [
            'secret' => $totp->getSecret(),
            'otpauth_uri' => $totp->getProvisioningUri(),
        ];
    }
}
