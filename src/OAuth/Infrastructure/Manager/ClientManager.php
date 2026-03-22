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
        $event = $this->dispatcher->dispatch(
            new PreSaveClientEvent($client),
            OAuth2Events::PRE_SAVE_CLIENT
        );
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
        $this->applyFilterToQueryBuilder($queryBuilder, $clientFilter);

        $documents = $queryBuilder->getQuery()->execute()->toArray();

        return array_values($documents);
    }

    private function applyFilterToQueryBuilder(
        object $queryBuilder,
        ?ClientFilter $filter
    ): void {
        $filters = $this->buildFilters($filter);

        foreach ($filters as $field => $values) {
            $queryBuilder->field($field)->all($values);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildFilters(?ClientFilter $filter): array
    {
        if ($filter === null) {
            return [];
        }

        $filters = [
            'grants' => $this->stringifyValues($filter->getGrants()),
            'redirectUris' => $this->stringifyValues($filter->getRedirectUris()),
            'scopes' => $this->stringifyValues($filter->getScopes()),
        ];

        return array_filter($filters);
    }

    /**
     * @param array<int, Grant|RedirectUri|Scope> $values
     *
     * @return list<string>
     */
    private function stringifyValues(array $values): array
    {
        return array_values(array_map('strval', $values));
    }
}
