<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Entity;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * League access token whose JWT is signed by AWS KMS instead of the local
 * private key that league's AccessTokenTrait would use. The claims match
 * league's own token (aud, jti, iat, nbf, exp, sub, scopes).
 */
final class KmsSignedAccessToken implements AccessTokenEntityInterface
{
    use EntityTrait;
    use TokenEntityTrait;

    public function __construct(
        private readonly KmsJwtFactory $jwtFactory,
        private readonly CurrentTimestampProviderInterface $timestampProvider,
    ) {
    }

    /**
     * KMS holds the signing key; the league CryptKey carries no key material.
     */
    #[\Override]
    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {
    }

    #[\Override]
    public function toString(): string
    {
        $issuedAt = $this->timestampProvider->currentTimestamp();
        $clientId = $this->getClient()->getIdentifier();

        return $this->jwtFactory->create([
            'aud' => $clientId,
            'jti' => $this->getIdentifier(),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $this->getExpiryDateTime()->getTimestamp(),
            'sub' => $this->getUserIdentifier() ?? $clientId,
            'scopes' => array_map(
                static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
                $this->getScopes()
            ),
        ]);
    }
}
