<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class CredentialsRevoker implements CredentialsRevokerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ClientManagerInterface $clientManager,
    ) {
    }

    #[\Override]
    public function revokeCredentialsForUser(UserInterface $user): void
    {
        $userIdentifier = $user->getUserIdentifier();

        $this->revokeAccessTokensByUser($userIdentifier);
        $this->revokeAuthorizationCodesByUser($userIdentifier);
        $this->revokeRefreshTokensByUser($userIdentifier);

        $this->documentManager->flush();
    }

    #[\Override]
    public function revokeCredentialsForClient(AbstractClient $client): void
    {
        $storedClient = $this->clientManager->find($client->getIdentifier());

        if ($storedClient === null) {
            return;
        }

        $this->revokeAccessTokensByClient($storedClient);
        $this->revokeAuthorizationCodesByClient($storedClient);
        $this->revokeRefreshTokensByClient($storedClient);

        $this->documentManager->flush();
    }

    private function revokeAccessTokensByUser(string $userIdentifier): void
    {
        $this->documentManager->createQueryBuilder(AccessToken::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();
    }

    private function revokeAuthorizationCodesByUser(string $userIdentifier): void
    {
        $this->documentManager->createQueryBuilder(AuthorizationCode::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();
    }

    private function revokeRefreshTokensByUser(string $userIdentifier): void
    {
        $accessTokens = $this->documentManager->createQueryBuilder(AccessToken::class)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        $this->revokeRefreshTokensByAccessTokens($accessTokens);
    }

    private function revokeAccessTokensByClient(ClientInterface $client): void
    {
        $this->documentManager->createQueryBuilder(AccessToken::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('client')->references($client)
            ->getQuery()
            ->execute();
    }

    private function revokeAuthorizationCodesByClient(ClientInterface $client): void
    {
        $this->documentManager->createQueryBuilder(AuthorizationCode::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('client')->references($client)
            ->getQuery()
            ->execute();
    }

    private function revokeRefreshTokensByClient(ClientInterface $client): void
    {
        $accessTokens = $this->documentManager->createQueryBuilder(AccessToken::class)
            ->field('client')->references($client)
            ->getQuery()
            ->execute();

        $this->revokeRefreshTokensByAccessTokens($accessTokens);
    }

    private function revokeRefreshTokensByAccessTokens(iterable $accessTokens): void
    {
        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->getIdentifier();
        }

        if (empty($accessTokenIds)) {
            return;
        }

        $this->documentManager->createQueryBuilder(RefreshToken::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('accessToken')->in($accessTokenIds)
            ->getQuery()
            ->execute();
    }
}
