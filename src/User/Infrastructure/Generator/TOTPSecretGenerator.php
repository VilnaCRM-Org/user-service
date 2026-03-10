<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Generator;

use App\User\Application\Generator\TOTPSecretGeneratorInterface;
use App\User\Infrastructure\TwoFactor\TOTPCreatorInterface;

/**
 * @psalm-api
 */
final class TOTPSecretGenerator implements TOTPSecretGeneratorInterface
{
    private const OTP_ISSUER = 'VilnaCRM';

    public function __construct(private readonly TOTPCreatorInterface $totpCreator)
    {
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{secret: string, otpauth_uri: string}
     */
    #[\Override]
    public function generate(string $email): array
    {
        $totp = $this->totpCreator->create();
        $totp->setIssuer(self::OTP_ISSUER);
        $totp->setLabel($email);

        return [
            'secret' => $totp->getSecret(),
            'otpauth_uri' => $totp->getProvisioningUri(),
        ];
    }
}
