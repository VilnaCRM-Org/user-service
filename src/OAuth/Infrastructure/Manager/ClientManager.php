<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class ClientManager implements ClientManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function save(ClientInterface $client): void
    {
        // Convert value objects to strings before persisting
        $this->prepareForPersistence($client);

        $this->documentManager->persist($client);
        $this->documentManager->flush();
    }

    public function remove(ClientInterface $client): void
    {
        $this->documentManager->remove($client);
        $this->documentManager->flush();
    }

    public function find(string $identifier): ?ClientInterface
    {
        $client = $this->documentManager->find(
            \League\Bundle\OAuth2ServerBundle\Model\Client::class,
            $identifier
        );

        if ($client instanceof ClientInterface) {
            $this->hydrateFromPersistence($client);
        }

        return $client;
    }

    /**
     * @return list<ClientInterface>
     */
    public function list(?ClientFilter $clientFilter): array
    {
        $queryBuilder = $this->documentManager->createQueryBuilder(
            \League\Bundle\OAuth2ServerBundle\Model\Client::class
        );

        if ($clientFilter !== null && $clientFilter->hasFilters()) {
            $criteria = [];

            // Filter by grants using $all operator (all specified grants must be present)
            if (!empty($clientFilter->getGrants())) {
                $grantStrings = array_map(
                    static fn(Grant $grant): string => (string) $grant,
                    $clientFilter->getGrants()
                );
                $criteria['grants'] = ['$all' => $grantStrings];
            }

            // Filter by redirect URIs using $all operator
            if (!empty($clientFilter->getRedirectUris())) {
                $redirectUriStrings = array_map(
                    static fn(RedirectUri $uri): string => (string) $uri,
                    $clientFilter->getRedirectUris()
                );
                $criteria['redirectUris'] = ['$all' => $redirectUriStrings];
            }

            // Filter by scopes using $all operator
            if (!empty($clientFilter->getScopes())) {
                $scopeStrings = array_map(
                    static fn(Scope $scope): string => (string) $scope,
                    $clientFilter->getScopes()
                );
                $criteria['scopes'] = ['$all' => $scopeStrings];
            }

            foreach ($criteria as $field => $value) {
                $queryBuilder->field($field)->equals($value);
            }
        }

        $clients = $queryBuilder->getQuery()->execute()->toArray();

        // Hydrate value objects from strings
        foreach ($clients as $client) {
            if ($client instanceof ClientInterface) {
                $this->hydrateFromPersistence($client);
            }
        }

        return array_values($clients);
    }

    /**
     * Convert value objects to strings before persisting.
     */
    private function prepareForPersistence(ClientInterface $client): void
    {
        $reflection = new \ReflectionClass($client);

        // Convert grants to strings
        $grantsProperty = $reflection->getProperty('grants');
        $grantsProperty->setAccessible(true);
        $grants = $grantsProperty->getValue($client);
        $grantStrings = array_map(static fn(Grant $grant): string => (string) $grant, $grants);
        $grantsProperty->setValue($client, $grantStrings);

        // Convert redirect URIs to strings
        $redirectUrisProperty = $reflection->getProperty('redirectUris');
        $redirectUrisProperty->setAccessible(true);
        $redirectUris = $redirectUrisProperty->getValue($client);
        $redirectUriStrings = array_map(static fn(RedirectUri $uri): string => (string) $uri, $redirectUris);
        $redirectUrisProperty->setValue($client, $redirectUriStrings);

        // Convert scopes to strings
        $scopesProperty = $reflection->getProperty('scopes');
        $scopesProperty->setAccessible(true);
        $scopes = $scopesProperty->getValue($client);
        $scopeStrings = array_map(static fn(Scope $scope): string => (string) $scope, $scopes);
        $scopesProperty->setValue($client, $scopeStrings);
    }

    /**
     * Convert strings back to value objects after loading from database.
     */
    private function hydrateFromPersistence(ClientInterface $client): void
    {
        $reflection = new \ReflectionClass($client);

        // Convert grant strings back to Grant objects
        $grantsProperty = $reflection->getProperty('grants');
        $grantsProperty->setAccessible(true);
        $grantStrings = $grantsProperty->getValue($client);
        if (is_array($grantStrings) && !empty($grantStrings) && !$grantStrings[0] instanceof Grant) {
            $grants = array_map(static fn(string $grant): Grant => new Grant($grant), $grantStrings);
            $grantsProperty->setValue($client, $grants);
        }

        // Convert redirect URI strings back to RedirectUri objects
        $redirectUrisProperty = $reflection->getProperty('redirectUris');
        $redirectUrisProperty->setAccessible(true);
        $redirectUriStrings = $redirectUrisProperty->getValue($client);
        if (is_array($redirectUriStrings) && !empty($redirectUriStrings) && !$redirectUriStrings[0] instanceof RedirectUri) {
            $redirectUris = array_map(static fn(string $uri): RedirectUri => new RedirectUri($uri), $redirectUriStrings);
            $redirectUrisProperty->setValue($client, $redirectUris);
        }

        // Convert scope strings back to Scope objects
        $scopesProperty = $reflection->getProperty('scopes');
        $scopesProperty->setAccessible(true);
        $scopeStrings = $scopesProperty->getValue($client);
        if (is_array($scopeStrings) && !empty($scopeStrings) && !$scopeStrings[0] instanceof Scope) {
            $scopes = array_map(static fn(string $scope): Scope => new Scope($scope), $scopeStrings);
            $scopesProperty->setValue($client, $scopes);
        }
    }
}
