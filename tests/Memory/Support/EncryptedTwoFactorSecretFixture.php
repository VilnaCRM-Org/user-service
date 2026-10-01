<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use OTPHP\TOTP;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the stored 2FA secret of a fixture user the way the application does
 * (S5.12): encrypted with the KMS 2FA key under the encryption context of the
 * user's id, or null when 2FA is disabled.
 */
final readonly class EncryptedTwoFactorSecretFixture
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function secretFor(User $user): ?string
    {
        if (!$user->isTwoFactorEnabled()) {
            return null;
        }

        $encryptor = $this->container->get(TwoFactorSecretEncryptorInterface::class);
        \assert($encryptor instanceof TwoFactorSecretEncryptorInterface);

        return $encryptor->encrypt(TOTP::generate()->getSecret(), $user->getId());
    }
}
