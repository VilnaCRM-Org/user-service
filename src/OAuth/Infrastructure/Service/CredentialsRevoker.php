<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Service;

use App\OAuth\Domain\Entity\AccessTokenDocument;
use App\OAuth\Domain\Entity\AuthorizationCodeDocument;
use App\OAuth\Domain\Entity\RefreshTokenDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class CredentialsRevoker implements CredentialsRevokerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    #[\Override]
    public function revokeCredentialsForUser(UserInterface $user): void
    {
        $userIdentifier = $user->getUserIdentifier();

        // Revoke access tokens for user
        $this->documentManager->createQueryBuilder(AccessTokenDocument::class)
            ->update()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        // Revoke authorization codes for user
        $this->documentManager->createQueryBuilder(AuthorizationCodeDocument::class)
            ->update()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        // Revoke refresh tokens - find access tokens for user first
        $accessTokens = $this->documentManager->createQueryBuilder(AccessTokenDocument::class)
            ->field('userIdentifier')->equals($userIdentifier)
            ->select('identifier')
            ->getQuery()
            ->execute();

        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->identifier;
        }

        if (!empty($accessTokenIds)) {
            $this->documentManager->createQueryBuilder(RefreshTokenDocument::class)
                ->update()
                ->field('revoked')->set(true)
                ->field('accessTokenIdentifier')->in($accessTokenIds)
                ->getQuery()
                ->execute();
        }

        $this->documentManager->flush();
    }

    #[\Override]
    public function revokeCredentialsForClient(AbstractClient $client): void
    {
        $clientIdentifier = $client->getIdentifier();

        // Revoke access tokens for client
        $this->documentManager->createQueryBuilder(AccessTokenDocument::class)
            ->update()
            ->field('revoked')->set(true)
            ->field('clientIdentifier')->equals($clientIdentifier)
            ->getQuery()
            ->execute();

        // Revoke authorization codes for client
        $this->documentManager->createQueryBuilder(AuthorizationCodeDocument::class)
            ->update()
            ->field('revoked')->set(true)
            ->field('clientIdentifier')->equals($clientIdentifier)
            ->getQuery()
            ->execute();

        // Revoke refresh tokens - find access tokens for client first
        $accessTokens = $this->documentManager->createQueryBuilder(AccessTokenDocument::class)
            ->field('clientIdentifier')->equals($clientIdentifier)
            ->select('identifier')
            ->getQuery()
            ->execute();

        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->identifier;
        }

        if (!empty($accessTokenIds)) {
            $this->documentManager->createQueryBuilder(RefreshTokenDocument::class)
                ->update()
                ->field('revoked')->set(true)
                ->field('accessTokenIdentifier')->in($accessTokenIds)
                ->getQuery()
                ->execute();
        }

        $this->documentManager->flush();
    }
}
