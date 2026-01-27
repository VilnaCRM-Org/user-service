<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
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

        $this->documentManager->createQueryBuilder(AccessToken::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        $this->documentManager->createQueryBuilder(AuthorizationCode::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        $accessTokens = $this->documentManager->createQueryBuilder(AccessToken::class)
            ->field('userIdentifier')->equals($userIdentifier)
            ->getQuery()
            ->execute();

        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->getIdentifier();
        }

        if (!empty($accessTokenIds)) {
            $this->documentManager->createQueryBuilder(RefreshToken::class)
                ->updateMany()
                ->field('revoked')->set(true)
                ->field('accessToken')->in($accessTokenIds)
                ->getQuery()
                ->execute();
        }

        $this->documentManager->flush();
    }

    #[\Override]
    public function revokeCredentialsForClient(AbstractClient $client): void
    {
        $storedClient = $this->clientManager->find($client->getIdentifier());

        if ($storedClient === null) {
            return;
        }

        $this->documentManager->createQueryBuilder(AccessToken::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('client')->references($storedClient)
            ->getQuery()
            ->execute();

        $this->documentManager->createQueryBuilder(AuthorizationCode::class)
            ->updateMany()
            ->field('revoked')->set(true)
            ->field('client')->references($storedClient)
            ->getQuery()
            ->execute();

        $accessTokens = $this->documentManager->createQueryBuilder(AccessToken::class)
            ->field('client')->references($storedClient)
            ->getQuery()
            ->execute();

        $accessTokenIds = [];
        foreach ($accessTokens as $token) {
            $accessTokenIds[] = $token->getIdentifier();
        }

        if (!empty($accessTokenIds)) {
            $this->documentManager->createQueryBuilder(RefreshToken::class)
                ->updateMany()
                ->field('revoked')->set(true)
                ->field('accessToken')->in($accessTokenIds)
                ->getQuery()
                ->execute();
        }

        $this->documentManager->flush();
    }
}
