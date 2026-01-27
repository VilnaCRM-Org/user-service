<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use App\OAuth\Domain\Entity\AuthorizationCodeDocument;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use RuntimeException;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ClientManager $clientManager,
    ) {
    }

    #[\Override]
    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        $document = $this->documentManager->find(
            AuthorizationCodeDocument::class,
            $identifier
        );

        if ($document === null) {
            return null;
        }

        return $this->toModel($document);
    }

    #[\Override]
    public function save(AuthorizationCodeInterface $authCode): void
    {
        $document = $this->toDocument($authCode);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    #[\Override]
    public function clearExpired(): int
    {
        // Count expired codes first
        $countQuery = $this->documentManager->createQueryBuilder(
            AuthorizationCodeDocument::class
        )
            ->field('expiry')->lt(new DateTimeImmutable())
            ->count()
            ->getQuery()
            ->execute();

        // Remove expired codes
        $this->documentManager->createQueryBuilder(
            AuthorizationCodeDocument::class
        )
            ->remove()
            ->field('expiry')->lt(new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return (int) $countQuery;
    }

    /**
     * Convert bundle AuthorizationCode model to AuthorizationCodeDocument DTO.
     */
    private function toDocument(AuthorizationCodeInterface $authCode): AuthorizationCodeDocument
    {
        $document = new AuthorizationCodeDocument();
        $document->identifier = $authCode->getIdentifier();
        $document->expiry = $authCode->getExpiryDateTime();
        $document->userIdentifier = $authCode->getUserIdentifier();
        $document->clientIdentifier = $authCode->getClient()->getIdentifier();
        $document->revoked = $authCode->isRevoked();

        // Convert scopes to strings
        $document->scopes = array_map(
            static fn (Scope $scope): string => (string) $scope,
            $authCode->getScopes()
        );

        return $document;
    }

    /**
     * Convert AuthorizationCodeDocument DTO to bundle AuthorizationCode model.
     */
    private function toModel(AuthorizationCodeDocument $document): AuthorizationCode
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

        $authCode = new AuthorizationCode(
            $document->identifier,
            $document->expiry,
            $client,
            $document->userIdentifier,
            $scopes
        );

        if ($document->revoked) {
            $authCode->revoke();
        }

        return $authCode;
    }
}
