<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        $authCode = $this->documentManager->find(
            \League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode::class,
            $identifier
        );

        if ($authCode instanceof AuthorizationCodeInterface) {
            $this->hydrateFromPersistence($authCode);
        }

        return $authCode;
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        // Convert value objects to strings before persisting
        $this->prepareForPersistence($authCode);

        $this->documentManager->persist($authCode);
        $this->documentManager->flush();
    }

    public function clearExpired(): int
    {
        // Count expired codes first
        $countQuery = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode::class
        )
            ->field('expiry')->lt(new \DateTimeImmutable())
            ->count()
            ->getQuery()
            ->execute();

        // Remove expired codes
        $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode::class
        )
            ->remove()
            ->field('expiry')->lt(new \DateTimeImmutable())
            ->getQuery()
            ->execute();

        return (int) $countQuery;
    }

    /**
     * Convert value objects to strings before persisting.
     */
    private function prepareForPersistence(AuthorizationCodeInterface $authCode): void
    {
        $reflection = new \ReflectionClass($authCode);

        // Convert scopes to strings
        $scopesProperty = $reflection->getProperty('scopes');
        $scopesProperty->setAccessible(true);
        $scopes = $scopesProperty->getValue($authCode);
        $scopeStrings = array_map(static fn(Scope $scope): string => (string) $scope, $scopes);
        $scopesProperty->setValue($authCode, $scopeStrings);
    }

    /**
     * Convert strings back to value objects after loading from database.
     */
    private function hydrateFromPersistence(AuthorizationCodeInterface $authCode): void
    {
        $reflection = new \ReflectionClass($authCode);

        // Convert scope strings back to Scope objects
        $scopesProperty = $reflection->getProperty('scopes');
        $scopesProperty->setAccessible(true);
        $scopeStrings = $scopesProperty->getValue($authCode);
        if (is_array($scopeStrings) && !empty($scopeStrings) && !$scopeStrings[0] instanceof Scope) {
            $scopes = array_map(static fn(string $scope): Scope => new Scope($scope), $scopeStrings);
            $scopesProperty->setValue($authCode, $scopes);
        }
    }
}
