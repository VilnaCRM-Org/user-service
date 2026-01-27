<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use App\OAuth\Domain\Entity\AccessTokenDocument;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use RuntimeException;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class AccessTokenManager implements AccessTokenManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ClientManager $clientManager,
    ) {
    }

    #[\Override]
    public function find(string $identifier): ?AccessTokenInterface
    {
        $document = $this->documentManager->find(
            AccessTokenDocument::class,
            $identifier
        );

        if ($document === null) {
            return null;
        }

        return $this->toModel($document);
    }

    #[\Override]
    public function save(AccessTokenInterface $accessToken): void
    {
        $document = $this->toDocument($accessToken);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    #[\Override]
    public function clearExpired(): int
    {
        // Count expired tokens first
        $countQuery = $this->documentManager->createQueryBuilder(
            AccessTokenDocument::class
        )
            ->field('expiry')->lt(new DateTimeImmutable())
            ->count()
            ->getQuery()
            ->execute();

        // Remove expired tokens
        $this->documentManager->createQueryBuilder(
            AccessTokenDocument::class
        )
            ->remove()
            ->field('expiry')->lt(new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return (int) $countQuery;
    }

    /**
     * Convert bundle AccessToken model to AccessTokenDocument DTO.
     */
    private function toDocument(AccessTokenInterface $accessToken): AccessTokenDocument
    {
        $document = new AccessTokenDocument();
        $document->identifier = $accessToken->getIdentifier();
        $document->expiry = $accessToken->getExpiry();
        $document->userIdentifier = $accessToken->getUserIdentifier();
        $document->clientIdentifier = $accessToken->getClient()->getIdentifier();
        $document->revoked = $accessToken->isRevoked();

        // Convert scopes to strings
        $document->scopes = array_map(
            static fn (Scope $scope): string => (string) $scope,
            $accessToken->getScopes()
        );

        return $document;
    }

    /**
     * Convert AccessTokenDocument DTO to bundle AccessToken model.
     */
    private function toModel(AccessTokenDocument $document): AccessToken
    {
        // Load the client
        $client = $this->clientManager->find($document->clientIdentifier);

        if ($client === null) {
            throw new RuntimeException(
                sprintf('Client with identifier "%s" not found', $document->clientIdentifier)
            );
        }

        // Convert scope strings back to Scope objects
        $scopes = array_map(
            static fn (string $scope): Scope => new Scope($scope),
            $document->scopes
        );

        $accessToken = new AccessToken(
            $document->identifier,
            $document->expiry,
            $client,
            $document->userIdentifier,
            $scopes
        );

        if ($document->revoked) {
            $accessToken->revoke();
        }

        return $accessToken;
    }
}
