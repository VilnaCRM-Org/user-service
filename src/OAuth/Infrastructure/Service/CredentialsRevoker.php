<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class CredentialsRevoker implements CredentialsRevokerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function revokeCredentialsForUser(UserInterface $user): void
    {
        $userIdentifier = $user->getUserIdentifier();

        // Revoke access tokens for user
        $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class
        )
            ->update()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        // Revoke authorization codes for user
        $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode::class
        )
            ->update()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        // Revoke refresh tokens - find access tokens for user first, then revoke related refresh tokens
        $accessTokens = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class
        )
            ->field('userIdentifier')->equals($userIdentifier)
            ->select('_id')
            ->getQuery()
            ->execute();

        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->getIdentifier();
        }

        if (!empty($accessTokenIds)) {
            $this->documentManager->createQueryBuilder(
                \League\Bundle\OAuth2ServerBundle\Model\RefreshToken::class
            )
                ->update()
                ->field('revoked')->set(true)
                ->field('accessToken.$id')->in($accessTokenIds)
                ->getQuery()
                ->execute();
        }

        $this->documentManager->flush();
    }

    public function revokeCredentialsForClient(AbstractClient $client): void
    {
        $clientIdentifier = $client->getIdentifier();

        // Revoke access tokens for client
        $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class
        )
            ->update()
            ->field('revoked')->set(true)
            ->field('client.$id')->equals($clientIdentifier)
            ->getQuery()
            ->execute();

        // Revoke authorization codes for client
        $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode::class
        )
            ->update()
            ->field('revoked')->set(true)
            ->field('client.$id')->equals($clientIdentifier)
            ->getQuery()
            ->execute();

        // Revoke refresh tokens - find access tokens for client first
        $accessTokens = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class
        )
            ->field('client.$id')->equals($clientIdentifier)
            ->select('_id')
            ->getQuery()
            ->execute();

        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->getIdentifier();
        }

        if (!empty($accessTokenIds)) {
            $this->documentManager->createQueryBuilder(
                \League\Bundle\OAuth2ServerBundle\Model\RefreshToken::class
            )
                ->update()
                ->field('revoked')->set(true)
                ->field('accessToken.$id')->in($accessTokenIds)
                ->getQuery()
                ->execute();
        }

        $this->documentManager->flush();
    }
}
