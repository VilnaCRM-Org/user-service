<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use App\OAuth\Domain\Entity\RefreshTokenDocument;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class RefreshTokenManager implements RefreshTokenManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly AccessTokenManagerInterface $accessTokenManager,
    ) {
    }

    #[\Override]
    public function find(string $identifier): ?RefreshTokenInterface
    {
        $document = $this->documentManager->find(
            RefreshTokenDocument::class,
            $identifier
        );

        if ($document === null) {
            return null;
        }

        return $this->toModel($document);
    }

    #[\Override]
    public function save(RefreshTokenInterface $refreshToken): void
    {
        $document = $this->toDocument($refreshToken);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    #[\Override]
    public function clearExpired(): int
    {
        // Count expired tokens first
        $countQuery = $this->documentManager->createQueryBuilder(
            RefreshTokenDocument::class
        )
            ->field('expiry')->lt(new DateTimeImmutable())
            ->count()
            ->getQuery()
            ->execute();

        // Remove expired tokens
        $this->documentManager->createQueryBuilder(
            RefreshTokenDocument::class
        )
            ->remove()
            ->field('expiry')->lt(new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return (int) $countQuery;
    }

    /**
     * Convert bundle RefreshToken model to RefreshTokenDocument DTO.
     */
    private function toDocument(RefreshTokenInterface $refreshToken): RefreshTokenDocument
    {
        $document = new RefreshTokenDocument();
        $document->identifier = $refreshToken->getIdentifier();
        $document->expiry = $refreshToken->getExpiry();
        $document->revoked = $refreshToken->isRevoked();

        // Store reference to access token if it exists
        $accessToken = $refreshToken->getAccessToken();
        if ($accessToken !== null) {
            $document->accessTokenIdentifier = $accessToken->getIdentifier();
        }

        return $document;
    }

    /**
     * Convert RefreshTokenDocument DTO to bundle RefreshToken model.
     */
    private function toModel(RefreshTokenDocument $document): RefreshToken
    {
        // Load the access token if referenced
        $accessToken = null;
        if ($document->accessTokenIdentifier !== null) {
            $accessToken = $this->accessTokenManager->find($document->accessTokenIdentifier);
        }

        $refreshToken = new RefreshToken(
            $document->identifier,
            $document->expiry,
            $accessToken
        );

        if ($document->revoked) {
            $refreshToken->revoke();
        }

        return $refreshToken;
    }
}
