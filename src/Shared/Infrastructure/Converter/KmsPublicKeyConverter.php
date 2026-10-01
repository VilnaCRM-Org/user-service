<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Converter;

use App\Shared\Domain\ValueObject\JwtVerificationKey;
use RuntimeException;

/**
 * Turns the DER SubjectPublicKeyInfo returned by KMS GetPublicKey into a
 * verification key whose kid is the RFC 7638 SHA-256 JWK thumbprint.
 */
final readonly class KmsPublicKeyConverter
{
    public function __construct(private Base64UrlConverter $base64Url)
    {
    }

    public function convert(string $keyId, string $derPublicKey): JwtVerificationKey
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($derPublicKey), 64, "\n")
            . "-----END PUBLIC KEY-----\n";

        $rsa = $this->rsaComponents($pem);
        $modulus = $this->base64Url->encode($rsa['n']);
        $exponent = $this->base64Url->encode($rsa['e']);

        return new JwtVerificationKey(
            $keyId,
            $this->thumbprint($modulus, $exponent),
            $pem,
            $modulus,
            $exponent
        );
    }

    /**
     * @return array{n: string, e: string}
     */
    private function rsaComponents(string $pem): array
    {
        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            throw new RuntimeException('AWS KMS returned an unreadable JWT public key.');
        }

        $rsa = openssl_pkey_get_details($publicKey)['rsa'] ?? null;
        if (!is_array($rsa)) {
            throw new RuntimeException('The JWT signing key must be an RSA key.');
        }

        return ['n' => $rsa['n'], 'e' => $rsa['e']];
    }

    private function thumbprint(string $modulus, string $exponent): string
    {
        $canonicalJwk = sprintf('{"e":"%s","kty":"RSA","n":"%s"}', $exponent, $modulus);

        return $this->base64Url->encode(hash('sha256', $canonicalJwk, true));
    }
}
