<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Adapter;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Shared\Infrastructure\Validator\JwtSignatureVerifier;
use DateTimeInterface;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\CreatedJWS;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\LoadedJWS;

/**
 * Lexik JWS provider backed by AWS KMS: tokens are signed with kms:Sign and
 * verified locally by the KMS public key their "kid" names. Lexik's
 * LcobucciJWTEncoder wraps it as the "lexik_jwt_authentication.encoder".
 */
final readonly class KmsJwsProvider implements JWSProviderInterface
{
    public function __construct(
        private KmsJwtFactory $jwtFactory,
        private JwtSignatureVerifier $verifier,
        private CurrentTimestampProviderInterface $timestampProvider,
        private ?int $ttl,
    ) {
    }

    /**
     * @param array<string, \DateTimeInterface|array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $payload
     * @param array<string, bool|float|int|string|null> $header
     */
    #[\Override]
    public function create(array $payload, array $header = []): CreatedJWS
    {
        $now = $this->timestampProvider->currentTimestamp();
        $claims = ['iat' => $now];
        if ($this->ttl !== null) {
            $claims['exp'] = $now + $this->ttl;
        }

        foreach ($payload as $name => $value) {
            $claims[$name] = $value instanceof DateTimeInterface
                ? $value->getTimestamp()
                : $value;
        }

        return new CreatedJWS($this->jwtFactory->create($claims, $header), true);
    }

    #[\Override]
    public function load(string $token): LoadedJWS
    {
        $verified = $this->verifier->verify($token);
        if ($verified === null) {
            throw new InvalidArgumentException('The JWT signature could not be verified.');
        }

        $payload = $verified['payload'];

        return new LoadedJWS($payload, $this->isActive($payload), true, $verified['header']);
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $payload
     */
    private function isActive(array $payload): bool
    {
        $now = $this->timestampProvider->currentTimestamp();
        $notBefore = $payload['nbf'] ?? $now;

        return is_int($notBefore) && $notBefore <= $now;
    }
}
