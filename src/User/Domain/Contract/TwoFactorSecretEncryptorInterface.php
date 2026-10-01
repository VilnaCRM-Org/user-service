<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

/**
 * Encrypts a user's TOTP secret. The ciphertext is bound to the user: it
 * decrypts only with the same user id (KMS encryption context user_id).
 */
interface TwoFactorSecretEncryptorInterface
{
    public function encrypt(string $secret, string $userId): string;

    public function decrypt(string $payload, string $userId): string;
}
