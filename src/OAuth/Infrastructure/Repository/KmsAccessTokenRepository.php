<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Repository;

use App\OAuth\Infrastructure\Entity\KmsSignedAccessToken;
use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

/**
 * Decorates the league bundle's access token repository so every new access
 * token is a KMS-signed JWT; persistence and revocation stay with the bundle.
 */
final readonly class KmsAccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private AccessTokenRepositoryInterface $inner,
        private KmsJwtFactory $jwtFactory,
        private CurrentTimestampProviderInterface $timestampProvider,
    ) {
    }

    /**
     * @param array<array-key, ScopeEntityInterface> $scopes
     */
    #[\Override]
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null
    ): AccessTokenEntityInterface {
        $accessToken = new KmsSignedAccessToken($this->jwtFactory, $this->timestampProvider);
        $accessToken->setClient($clientEntity);
        if ($userIdentifier !== null && $userIdentifier !== '') {
            $accessToken->setUserIdentifier($userIdentifier);
        }

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    #[\Override]
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->inner->persistNewAccessToken($accessTokenEntity);
    }

    #[\Override]
    public function revokeAccessToken(string $tokenId): void
    {
        $this->inner->revokeAccessToken($tokenId);
    }

    #[\Override]
    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->inner->isAccessTokenRevoked($tokenId);
    }
}
