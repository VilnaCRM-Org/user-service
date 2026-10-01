<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Public half of a JWT signing key, read from AWS KMS GetPublicKey.
 *
 * keyId is the KMS key ARN that signs; kid is the RFC 7638 JWK thumbprint
 * that tokens carry in their header and that the JWK set publishes.
 */
final readonly class JwtVerificationKey
{
    public function __construct(
        private string $keyId,
        private string $kid,
        private string $pem,
        private string $modulus,
        private string $exponent,
    ) {
    }

    public function keyId(): string
    {
        return $this->keyId;
    }

    public function kid(): string
    {
        return $this->kid;
    }

    public function pem(): string
    {
        return $this->pem;
    }

    /**
     * @return array{kty: string, use: string, alg: string, kid: string, n: string, e: string}
     */
    public function toJwk(): array
    {
        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $this->kid,
            'n' => $this->modulus,
            'e' => $this->exponent,
        ];
    }
}
