<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\ClientInterface;

final class RecordingClientManager implements ClientManagerInterface
{
    private ?ClientInterface $removedClient = null;
    private ?ClientInterface $savedClient = null;

    public function __construct(private readonly ?ClientInterface $existingClient)
    {
    }

    #[\Override]
    public function save(ClientInterface $client): void
    {
        $this->savedClient = $client;
    }

    #[\Override]
    public function remove(ClientInterface $client): void
    {
        $this->removedClient = $client;
    }

    #[\Override]
    public function find(string $identifier): ?ClientInterface
    {
        return $this->existingClient;
    }

    /**
     * @return array<ClientInterface>
     */
    #[\Override]
    public function list(?ClientFilter $clientFilter): array
    {
        $clients = [];

        if ($this->existingClient !== null) {
            $clients[] = $this->existingClient;
        }

        if ($this->savedClient !== null && $this->savedClient !== $this->existingClient) {
            $clients[] = $this->savedClient;
        }

        return $clients;
    }

    public function removedClient(): ?ClientInterface
    {
        return $this->removedClient;
    }

    public function savedClient(): ?ClientInterface
    {
        return $this->savedClient;
    }
}
