<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use Aws\Kms\KmsClient;
use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Issues RS256 JWTs signed by AWS KMS Sign (RSASSA_PKCS1_V1_5_SHA_256 over the
 * SHA-256 digest of the signing input). Only the current key signs, and the
 * "kid" header names it. No private key ever leaves KMS.
 */
final readonly class KmsJwtFactory
{
    private const ALGORITHM = 'RS256';
    private const SIGNING_ALGORITHM = 'RSASSA_PKCS1_V1_5_SHA_256';

    public function __construct(
        private KmsClient $kmsClient,
        private JwtVerificationKeyProviderInterface $keyProvider,
        private SerializerInterface $serializer,
        private Base64UrlConverter $base64Url,
    ) {
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $claims
     * @param array<string, bool|float|int|string|null> $header
     */
    public function create(array $claims, array $header = []): string
    {
        $key = $this->keyProvider->current();
        $signingInput = sprintf(
            '%s.%s',
            $this->encodeSegment(
                [...$header, 'alg' => self::ALGORITHM, 'typ' => 'JWT', 'kid' => $key->kid()]
            ),
            $this->encodeSegment($claims)
        );

        return sprintf(
            '%s.%s',
            $signingInput,
            $this->base64Url->encode($this->sign($key->keyId(), $signingInput))
        );
    }

    private function sign(string $keyId, string $signingInput): string
    {
        $signature = $this->kmsClient->sign([
            'KeyId' => $keyId,
            'Message' => hash('sha256', $signingInput, true),
            'MessageType' => 'DIGEST',
            'SigningAlgorithm' => self::SIGNING_ALGORITHM,
        ])->get('Signature');

        if (!is_string($signature) || $signature === '') {
            throw new RuntimeException('AWS KMS returned no JWT signature.');
        }

        return $signature;
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $data
     */
    private function encodeSegment(array $data): string
    {
        return $this->base64Url->encode($this->serializer->encode($data, JsonEncoder::FORMAT));
    }
}
