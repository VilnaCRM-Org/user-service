<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use RuntimeException;

final readonly class TwoFactorSecretEncryptor implements
    TwoFactorSecretEncryptorInterface
{
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const CIPHER = 'aes-256-gcm';

    private string $key;

    public function __construct(string $twoFactorEncryptionKey)
    {
        $decodedKey = base64_decode($twoFactorEncryptionKey, true);
        if (!is_string($decodedKey) || strlen($decodedKey) !== 32) {
            throw new RuntimeException('TWO_FACTOR_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
        }

        $this->key = $decodedKey;
    }

    #[\Override]
    public function encrypt(string $secret): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $cipherText = openssl_encrypt(
            $secret,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if (!is_string($cipherText)) {
            throw new RuntimeException('Failed to encrypt two-factor secret.');
        }

        return base64_encode($iv . $tag . $cipherText);
    }

    #[\Override]
    public function decrypt(string $payload): string
    {
        $decodedPayload = base64_decode($payload, true);
        if (!is_string($decodedPayload)) {
            throw new RuntimeException('Invalid encrypted payload encoding.');
        }

        $minimumLength = self::IV_LENGTH + self::TAG_LENGTH + 1;
        if (strlen($decodedPayload) < $minimumLength) {
            throw new RuntimeException('Invalid encrypted payload length.');
        }

        $iv = substr($decodedPayload, 0, self::IV_LENGTH);
        $tag = substr($decodedPayload, self::IV_LENGTH, self::TAG_LENGTH);
        $cipherText = substr($decodedPayload, self::IV_LENGTH + self::TAG_LENGTH);

        if (!is_string($iv) || !is_string($tag) || !is_string($cipherText)) {
            throw new RuntimeException('Invalid encrypted payload structure.');
        }

        $plainSecret = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (!is_string($plainSecret)) {
            throw new RuntimeException('Failed to decrypt two-factor secret.');
        }

        return $plainSecret;
    }
}
