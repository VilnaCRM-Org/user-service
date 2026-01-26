<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function find(string $identifier): ?AccessTokenInterface
    {
        $accessToken = $this->documentManager->find(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class,
            $identifier
        );

        if ($accessToken instanceof AccessTokenInterface) {
            $this->hydrateFromPersistence($accessToken);
        }

        return $accessToken;
    }

    public function save(AccessTokenInterface $accessToken): void
    {
        // Convert value objects to strings before persisting
        $this->prepareForPersistence($accessToken);

        $this->documentManager->persist($accessToken);
        $this->documentManager->flush();
    }

    public function clearExpired(): int
    {
        $queryBuilder = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class
        );

        $queryBuilder
            ->remove()
            ->field('expiry')->lt(new \DateTimeImmutable())
            ->getQuery()
            ->execute();

        // MongoDB doesn't return the count of deleted documents in the same way as SQL
        // We'll need to count first, then delete
        $countQuery = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AccessToken::class
        )
            ->field('expiry')->lt(new \DateTimeImmutable())
            ->count()
            ->getQuery()
            ->execute();

        return (int) $countQuery;
    }

    /**
     * Convert value objects to strings before persisting.
     */
    private function prepareForPersistence(AccessTokenInterface $accessToken): void
    {
        $reflection = new \ReflectionClass($accessToken);

        // Convert scopes to strings
        $scopesProperty = $reflection->getProperty('scopes');
        $scopesProperty->setAccessible(true);
        $scopes = $scopesProperty->getValue($accessToken);
        $scopeStrings = array_map(static fn(Scope $scope): string => (string) $scope, $scopes);
        $scopesProperty->setValue($accessToken, $scopeStrings);
    }

    /**
     * Convert strings back to value objects after loading from database.
     */
    private function hydrateFromPersistence(AccessTokenInterface $accessToken): void
    {
        $reflection = new \ReflectionClass($accessToken);

        // Convert scope strings back to Scope objects
        $scopesProperty = $reflection->getProperty('scopes');
        $scopesProperty->setAccessible(true);
        $scopeStrings = $scopesProperty->getValue($accessToken);
        if (is_array($scopeStrings) && !empty($scopeStrings) && !$scopeStrings[0] instanceof Scope) {
            $scopes = array_map(static fn(string $scope): Scope => new Scope($scope), $scopeStrings);
            $scopesProperty->setValue($accessToken, $scopes);
        }
    }
}
