<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Adapter;

use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use Aws\Exception\AwsException;
use Aws\Kms\KmsClient;
use Aws\ResultInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Encrypts TOTP secrets with the symmetric 2FA KMS key (S5.12, FR-06).
 *
 * Every Encrypt and Decrypt call carries the encryption context
 * {user_id: <id>}, which the 2FA key policy requires. A ciphertext therefore
 * decrypts only for the user it was created for; any KMS failure fails closed.
 */
final readonly class KmsTwoFactorSecretEncryptor implements
    TwoFactorSecretEncryptorInterface
{
    /** KMS Encrypt accepts at most 4096 bytes of plaintext. */
    public const MAX_SECRET_BYTES = 4096;

    /** Base64 length of the largest KMS ciphertext blob (6144 bytes). */
    public const MAX_PAYLOAD_LENGTH = 8192;

    private const ENCRYPTION_CONTEXT_KEY = 'user_id';
    private const ENCRYPTION_ALGORITHM = 'SYMMETRIC_DEFAULT';

    public function __construct(
        private KmsClient $kmsClient,
        private string $keyId
    ) {
    }

    #[\Override]
    public function encrypt(string $secret, string $userId): string
    {
        $this->assertSecretSize($secret);
        $request = $this->request($userId, ['Plaintext' => $secret]);

        try {
            $result = $this->kmsClient->encrypt($request);
        } catch (AwsException $exception) {
            throw $this->encryptionFailure($exception);
        }

        $ciphertext = $this->nonEmptyString($result, 'CiphertextBlob');
        if ($ciphertext === null) {
            throw $this->encryptionFailure();
        }

        return base64_encode($ciphertext);
    }

    #[\Override]
    public function decrypt(string $payload, string $userId): string
    {
        $request = $this->request(
            $userId,
            ['CiphertextBlob' => $this->decodePayload($payload)]
        );

        try {
            $result = $this->kmsClient->decrypt($request);
        } catch (AwsException $exception) {
            throw $this->decryptionFailure($exception);
        }

        $secret = $this->nonEmptyString($result, 'Plaintext');
        if ($secret === null) {
            throw $this->decryptionFailure();
        }

        return $secret;
    }

    /**
     * @param array<string, string> $data
     *
     * @return array<string, array<string, string>|string>
     */
    private function request(string $userId, array $data): array
    {
        if (trim($userId) === '') {
            throw new InvalidArgumentException(
                'Two-factor encryption context user_id must not be empty.'
            );
        }

        return [
            'KeyId' => $this->keyId,
            'EncryptionContext' => [self::ENCRYPTION_CONTEXT_KEY => $userId],
            'EncryptionAlgorithm' => self::ENCRYPTION_ALGORITHM,
        ] + $data;
    }

    private function assertSecretSize(string $secret): void
    {
        $length = strlen($secret);
        if ($length === 0 || $length > self::MAX_SECRET_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Two-factor secret must be between 1 and %d bytes.',
                self::MAX_SECRET_BYTES
            ));
        }
    }

    private function decodePayload(string $payload): string
    {
        if (strlen($payload) > self::MAX_PAYLOAD_LENGTH) {
            throw $this->invalidPayload();
        }

        $ciphertext = base64_decode($payload, true);
        if (!is_string($ciphertext) || $ciphertext === '') {
            throw $this->invalidPayload();
        }

        return $ciphertext;
    }

    private function nonEmptyString(ResultInterface $result, string $field): ?string
    {
        $value = $result->get($field);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function encryptionFailure(?AwsException $previous = null): RuntimeException
    {
        return new RuntimeException('Failed to encrypt two-factor secret.', previous: $previous);
    }

    private function decryptionFailure(?AwsException $previous = null): RuntimeException
    {
        return new RuntimeException('Failed to decrypt two-factor secret.', previous: $previous);
    }

    private function invalidPayload(): InvalidArgumentException
    {
        return new InvalidArgumentException('Two-factor secret payload is invalid.');
    }
}
