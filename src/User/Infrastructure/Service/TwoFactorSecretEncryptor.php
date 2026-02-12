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
            throw new RuntimeException(
                'TWO_FACTOR_ENCRYPTION_KEY must be 32-byte base64.'
            );
        }

        $this->key = $decodedKey;
    }

    /**
     * @return string
     */
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

        if (!is_string($cipherText)) { // @infection-ignore-all
            throw new RuntimeException('Failed to encrypt two-factor secret.'); // @infection-ignore-all
        }

        return base64_encode($iv . $tag . $cipherText);
    }

    #[\Override]
    public function decrypt(string $payload): string
    {
        $raw = $this->decodePayload($payload);
        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ct = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);
        $plain = openssl_decrypt(
            $ct,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if (!is_string($plain)) {
            throw new RuntimeException('Failed to decrypt secret.');
        }

        return $plain;
    }

    private function decodePayload(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if (!is_string($raw)) {
            throw new RuntimeException('Invalid payload encoding.');
        }
        if (strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH + 1) {
            throw new RuntimeException('Invalid payload length.');
        }

        return $raw;
    }
}
