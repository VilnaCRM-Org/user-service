<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use App\OAuth\Domain\Entity\ClientDocument;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class ClientManager implements ClientManagerInterface
{
    #[\Override]
    public function save(ClientInterface $client): void
    {
        $document = $this->toDocument($client);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    #[\Override]
    public function remove(ClientInterface $client): void
    {
        $document = $this->documentManager->find(
            ClientDocument::class,
            $client->getIdentifier()
        );

        if ($document !== null) {
            $this->documentManager->remove($document);
            $this->documentManager->flush();
        }
    }

    #[\Override]
    public function find(string $identifier): ?ClientInterface
    {
        $document = $this->documentManager->find(
            ClientDocument::class,
            $identifier
        );

        if ($document === null) {
            return null;
        }

        return $this->toModel($document);
    }

    /**
     * @return list<ClientInterface>
     */
    #[\Override]
    public function list(?ClientFilter $clientFilter): array
    {
        $queryBuilder = $this->documentManager->createQueryBuilder(ClientDocument::class);

        if ($clientFilter !== null && $clientFilter->hasFilters()) {
            // Filter by grants using $all operator (all specified grants must be present)
            if (!empty($clientFilter->getGrants())) {
                $grantStrings = array_map(
                    static fn (Grant $grant): string => (string) $grant,
                    $clientFilter->getGrants()
                );
                $queryBuilder->field('grants')->all($grantStrings);
            }

            // Filter by redirect URIs using $all operator
            if (!empty($clientFilter->getRedirectUris())) {
                $redirectUriStrings = array_map(
                    static fn (RedirectUri $uri): string => (string) $uri,
                    $clientFilter->getRedirectUris()
                );
                $queryBuilder->field('redirectUris')->all($redirectUriStrings);
            }

            // Filter by scopes using $all operator
            if (!empty($clientFilter->getScopes())) {
                $scopeStrings = array_map(
                    static fn (Scope $scope): string => (string) $scope,
                    $clientFilter->getScopes()
                );
                $queryBuilder->field('scopes')->all($scopeStrings);
            }
        }

        $documents = $queryBuilder->getQuery()->execute()->toArray();

        return array_map(
            fn (ClientDocument $document): ClientInterface => $this->toModel($document),
            array_values($documents)
        );
    }

    /**
     * Convert bundle Client model to ClientDocument DTO.
     */
    private function toDocument(ClientInterface $client): ClientDocument
    {
        $document = new ClientDocument();
        $document->identifier = $client->getIdentifier();
        $document->name = $client->getName();
        $document->secret = $client->getSecret();
        $document->active = $client->isActive();
        $document->allowPlainTextPkce = $client->isPlainTextPkceAllowed();

        // Convert value objects to strings
        $document->redirectUris = array_map(
            static fn (RedirectUri $uri): string => (string) $uri,
            $client->getRedirectUris()
        );

        $document->grants = array_map(
            static fn (Grant $grant): string => (string) $grant,
            $client->getGrants()
        );

        $document->scopes = array_map(
            static fn (Scope $scope): string => (string) $scope,
            $client->getScopes()
        );

        return $document;
    }

    /**
     * Convert ClientDocument DTO to bundle Client model.
     */
    private function toModel(ClientDocument $document): Client
    {
        $client = new Client(
            $document->name,
            $document->identifier,
            $document->secret
        );

        // Convert string arrays back to value objects
        $redirectUris = array_map(
            static fn (string $uri): RedirectUri => new RedirectUri($uri),
            $document->redirectUris
        );

        $grants = array_map(
            static fn (string $grant): Grant => new Grant($grant),
            $document->grants
        );

        $scopes = array_map(
            static fn (string $scope): Scope => new Scope($scope),
            $document->scopes
        );

        if (!empty($redirectUris)) {
            $client->setRedirectUris(...$redirectUris);
        }

        if (!empty($grants)) {
            $client->setGrants(...$grants);
        }

        if (!empty($scopes)) {
            $client->setScopes(...$scopes);
        }

        $client->setActive($document->active);
        $client->setAllowPlainTextPkce($document->allowPlainTextPkce);

        return $client;
    }
}
