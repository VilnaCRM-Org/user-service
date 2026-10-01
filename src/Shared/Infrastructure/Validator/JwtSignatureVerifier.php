<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use App\Shared\Domain\ValueObject\JwtVerificationKey;
use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Verifies an RS256 JWS locally with the KMS public key its "kid" names.
 *
 * Fails closed: any other "alg", a missing or unknown "kid", a malformed
 * segment or a bad signature returns null. Key lookup errors propagate.
 */
final readonly class JwtSignatureVerifier
{
    private const ALGORITHM = 'RS256';

    public function __construct(
        private JwtVerificationKeyProviderInterface $keyProvider,
        private SerializerInterface $serializer,
        private Base64UrlConverter $base64Url,
    ) {
    }

    /**
     * @return array{header: array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>, payload: array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>}|null
     */
    public function verify(string $token): ?array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $header = $this->decodeSegment($encodedHeader);
        $payload = $this->decodeSegment($encodedPayload);
        $signature = $this->base64Url->decode($encodedSignature);
        if ($header === null || $payload === null || $signature === null) {
            return null;
        }

        $signingInput = sprintf('%s.%s', $encodedHeader, $encodedPayload);

        return $this->hasValidSignature($header, $signingInput, $signature)
            ? ['header' => $header, 'payload' => $payload]
            : null;
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $header
     */
    private function hasValidSignature(
        array $header,
        string $signingInput,
        string $signature
    ): bool {
        $key = $this->resolveKey($header);

        return $key instanceof JwtVerificationKey
            && openssl_verify($signingInput, $signature, $key->pem(), OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $header
     */
    private function resolveKey(array $header): ?JwtVerificationKey
    {
        $kid = $header['kid'] ?? null;
        if (($header['alg'] ?? null) !== self::ALGORITHM || !is_string($kid)) {
            return null;
        }

        return $this->keyProvider->findByKid($kid);
    }

    /**
     * @return array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>|null
     */
    private function decodeSegment(string $segment): ?array
    {
        $json = $this->base64Url->decode($segment);
        if ($json === null) {
            return null;
        }

        try {
            $decoded = $this->serializer->decode($json, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
