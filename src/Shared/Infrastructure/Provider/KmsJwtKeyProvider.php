<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Provider;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use App\Shared\Domain\ValueObject\JwtVerificationKey;
use App\Shared\Infrastructure\Converter\KmsPublicKeyConverter;
use Aws\Kms\KmsClient;
use RuntimeException;

/**
 * Reads the JWT public keys from AWS KMS GetPublicKey and keeps them in
 * process memory for a bounded TTL. The current key (JWT_KMS_KEY_ID) signs;
 * the previous key (JWT_KMS_PREVIOUS_KEY_ID, empty outside a key-change
 * window) only verifies. A KMS error propagates, so callers fail closed.
 */
final class KmsJwtKeyProvider implements JwtVerificationKeyProviderInterface
{
    private const KEY_USAGE = 'SIGN_VERIFY';
    private const SIGNING_ALGORITHM = 'RSASSA_PKCS1_V1_5_SHA_256';

    /** @var array<string, array{expiresAt: int, key: JwtVerificationKey}> */
    private array $keys = [];

    public function __construct(
        private readonly KmsClient $kmsClient,
        private readonly KmsPublicKeyConverter $converter,
        private readonly CurrentTimestampProviderInterface $timestampProvider,
        private readonly string $currentKeyId,
        private readonly string $previousKeyId,
        private readonly int $cacheTtlSeconds,
    ) {
    }

    #[\Override]
    public function current(): JwtVerificationKey
    {
        return $this->resolve($this->currentKeyId);
    }

    /**
     * @return list<JwtVerificationKey>
     */
    #[\Override]
    public function verificationKeys(): array
    {
        $keys = [$this->current()];
        if ($this->previousKeyId !== '') {
            $keys[] = $this->resolve($this->previousKeyId);
        }

        return $keys;
    }

    /**
     * Resolves the previous key only when the kid is not the current key's, so
     * current-key tokens keep verifying even if the previous key is unavailable.
     */
    #[\Override]
    public function findByKid(string $kid): ?JwtVerificationKey
    {
        $current = $this->current();
        if ($current->kid() === $kid) {
            return $current;
        }

        if ($this->previousKeyId === '') {
            return null;
        }

        $previous = $this->resolve($this->previousKeyId);

        return $previous->kid() === $kid ? $previous : null;
    }

    private function resolve(string $keyId): JwtVerificationKey
    {
        $now = $this->timestampProvider->currentTimestamp();
        $cached = $this->keys[$keyId] ?? null;
        if ($cached !== null && $cached['expiresAt'] > $now) {
            return $cached['key'];
        }

        $key = $this->fetch($keyId);
        $this->keys[$keyId] = ['expiresAt' => $now + $this->cacheTtlSeconds, 'key' => $key];

        return $key;
    }

    private function fetch(string $keyId): JwtVerificationKey
    {
        $result = $this->kmsClient->getPublicKey(['KeyId' => $keyId]);
        $algorithms = $result['SigningAlgorithms'] ?? [];

        if (
            $result['KeyUsage'] !== self::KEY_USAGE
            || !in_array(self::SIGNING_ALGORITHM, $algorithms, true)
        ) {
            throw new RuntimeException(sprintf(
                'The JWT KMS key must be a %s key that supports %s.',
                self::KEY_USAGE,
                self::SIGNING_ALGORITHM
            ));
        }

        return $this->converter->convert($result['KeyId'], $result['PublicKey']);
    }
}
