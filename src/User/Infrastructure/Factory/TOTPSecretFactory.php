<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use App\User\Application\Factory\TOTPSecretFactoryInterface;

/**
 * @psalm-api
 */
final class TOTPSecretFactory implements TOTPSecretFactoryInterface
{
    private const OTP_ISSUER = 'VilnaCRM';

    public function __construct(private readonly TOTPFactoryInterface $totpCreator)
    {
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{secret: string, otpauth_uri: string}
     */
    #[\Override]
    public function create(string $email): array
    {
        return $this->totpCreator->create($email, self::OTP_ISSUER);
    }
}
