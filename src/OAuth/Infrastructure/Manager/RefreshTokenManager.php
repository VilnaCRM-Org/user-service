<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

final class RefreshTokenManager implements RefreshTokenManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function find(string $identifier): ?RefreshTokenInterface
    {
        return $this->documentManager->find(
            \League\Bundle\OAuth2ServerBundle\Model\RefreshToken::class,
            $identifier
        );
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->documentManager->persist($refreshToken);
        $this->documentManager->flush();
    }

    public function clearExpired(): int
    {
        // Count expired tokens first
        $countQuery = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\RefreshToken::class
        )
            ->field('expiry')->lt(new \DateTimeImmutable())
            ->count()
            ->getQuery()
            ->execute();

        // Remove expired tokens
        $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\RefreshToken::class
        )
            ->remove()
            ->field('expiry')->lt(new \DateTimeImmutable())
            ->getQuery()
            ->execute();

        return (int) $countQuery;
    }
}
