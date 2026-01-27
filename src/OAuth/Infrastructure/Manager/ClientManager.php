<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientManager implements ClientManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    #[\Override]
    public function save(ClientInterface $client): void
    {
        $event = $this->dispatcher->dispatch(new PreSaveClientEvent($client), OAuth2Events::PRE_SAVE_CLIENT);
        $client = $event->getClient();

        $this->documentManager->persist($client);
        $this->documentManager->flush();
    }

    #[\Override]
    public function remove(ClientInterface $client): void
    {
        $this->documentManager->remove($client);
        $this->documentManager->flush();
    }

    #[\Override]
    public function find(string $identifier): ?ClientInterface
    {
        return $this->documentManager->find(Client::class, $identifier);
    }

    /**
     * @return list<ClientInterface>
     */
    #[\Override]
    public function list(?ClientFilter $clientFilter): array
    {
        $queryBuilder = $this->documentManager->createQueryBuilder(Client::class);

        if ($clientFilter !== null && $clientFilter->hasFilters()) {
            $this->applyFilterToQueryBuilder($queryBuilder, $clientFilter);
        }

        $documents = $queryBuilder->getQuery()->execute()->toArray();

        return array_values($documents);
    }

    private function applyFilterToQueryBuilder(object $queryBuilder, ClientFilter $filter): void
    {
        $this->applyGrantsFilter($queryBuilder, $filter);
        $this->applyRedirectUrisFilter($queryBuilder, $filter);
        $this->applyScopesFilter($queryBuilder, $filter);
    }

    private function applyGrantsFilter(object $queryBuilder, ClientFilter $filter): void
    {
        if (empty($filter->getGrants())) {
            return;
        }

        $grantStrings = array_map(
            static fn (Grant $grant): string => (string) $grant,
            $filter->getGrants()
        );
        $queryBuilder->field('grants')->all($grantStrings);
    }

    private function applyRedirectUrisFilter(object $queryBuilder, ClientFilter $filter): void
    {
        if (empty($filter->getRedirectUris())) {
            return;
        }

        $redirectUriStrings = array_map(
            static fn (RedirectUri $uri): string => (string) $uri,
            $filter->getRedirectUris()
        );
        $queryBuilder->field('redirectUris')->all($redirectUriStrings);
    }

    private function applyScopesFilter(object $queryBuilder, ClientFilter $filter): void
    {
        if (empty($filter->getScopes())) {
            return;
        }

        $scopeStrings = array_map(
            static fn (Scope $scope): string => (string) $scope,
            $filter->getScopes()
        );
        $queryBuilder->field('scopes')->all($scopeStrings);
    }
}
