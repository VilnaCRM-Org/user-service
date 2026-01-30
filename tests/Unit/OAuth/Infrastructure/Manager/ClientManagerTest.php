<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Manager;

use App\OAuth\Infrastructure\Manager\ClientManager;
use App\Tests\Unit\OAuth\Infrastructure\OAuthInfrastructureTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientManagerTest extends OAuthInfrastructureTestCase
{
    public function testSaveDispatchesEventAndPersistsClient(): void
    {
        $client = $this->makeClient();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(PreSaveClientEvent::class),
                OAuth2Events::PRE_SAVE_CLIENT
            )
            ->willReturnCallback(
                static fn (PreSaveClientEvent $event): PreSaveClientEvent => $event
            );

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())->method('persist')->with($client);
        $documentManager->expects($this->once())->method('flush');

        $manager = new ClientManager($documentManager, $dispatcher);

        $manager->save($client);
    }

    public function testRemoveDeletesClient(): void
    {
        $client = $this->makeClient();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())->method('remove')->with($client);
        $documentManager->expects($this->once())->method('flush');

        $manager = new ClientManager($documentManager, $dispatcher);

        $manager->remove($client);
    }

    public function testFindReturnsClientWhenFound(): void
    {
        $client = $this->makeClient();
        $identifier = $client->getIdentifier();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('find')
            ->with(Client::class, $identifier)
            ->willReturn($client);

        $manager = new ClientManager($documentManager, $dispatcher);

        $this->assertSame($client, $manager->find($identifier));
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $identifier = $this->faker->lexify('missing_????????');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('find')
            ->with(Client::class, $identifier)
            ->willReturn(null);

        $manager = new ClientManager($documentManager, $dispatcher);

        $this->assertNull($manager->find($identifier));
    }

    public function testListAppliesFiltersAndReturnsClients(): void
    {
        $client = $this->makeClient();
        $result = new class([5 => $client]) {
            /**
             * @param array<int, Client> $items
             */
            public function __construct(private readonly array $items)
            {
            }

            /**
             * @return array<int, Client>
             */
            public function toArray(): array
            {
                return $this->items;
            }
        };
        $captures = [];
        $builder = $this->makeBuilder($result, $captures);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(Client::class)
            ->willReturn($builder);

        $grant = $this->faker->lexify('grant_????????');
        $redirectUri = $this->faker->url();
        $scope = $this->faker->lexify('scope_????????');

        $filter = ClientFilter::create()
            ->addGrantCriteria(new Grant($grant))
            ->addRedirectUriCriteria(new RedirectUri($redirectUri))
            ->addScopeCriteria(new Scope($scope));

        $manager = new ClientManager($documentManager, $dispatcher);

        $resultClients = $manager->list($filter);

        $this->assertSame([$client], $resultClients);
        $this->assertSame([$grant], $captures['all']['grants']);
        $this->assertSame([$redirectUri], $captures['all']['redirectUris']);
        $this->assertSame([$scope], $captures['all']['scopes']);
    }

    public function testListWithNullFilterReturnsAllClients(): void
    {
        $client = $this->makeClient();
        $result = new class([$client]) {
            /**
             * @param array<int, Client> $items
             */
            public function __construct(private readonly array $items)
            {
            }

            /**
             * @return array<int, Client>
             */
            public function toArray(): array
            {
                return $this->items;
            }
        };
        $captures = [];
        $builder = $this->makeBuilder($result, $captures);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(Client::class)
            ->willReturn($builder);

        $manager = new ClientManager($documentManager, $dispatcher);

        $resultClients = $manager->list(null);

        $this->assertSame([$client], $resultClients);
        $this->assertArrayNotHasKey('all', $captures);
    }

    public function testListWithEmptyFilterReturnsAllClients(): void
    {
        $client = $this->makeClient();
        $result = new class([$client]) {
            /**
             * @param array<int, Client> $items
             */
            public function __construct(private readonly array $items)
            {
            }

            /**
             * @return array<int, Client>
             */
            public function toArray(): array
            {
                return $this->items;
            }
        };
        $captures = [];
        $builder = $this->makeBuilder($result, $captures);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(Client::class)
            ->willReturn($builder);

        $filter = ClientFilter::create();

        $manager = new ClientManager($documentManager, $dispatcher);

        $resultClients = $manager->list($filter);

        $this->assertSame([$client], $resultClients);
        $this->assertArrayNotHasKey('all', $captures);
    }

    public function testListWithPartialFilterOnlyAppliesSetCriteria(): void
    {
        $client = $this->makeClient();
        $result = new class([$client]) {
            /**
             * @param array<int, Client> $items
             */
            public function __construct(private readonly array $items)
            {
            }

            /**
             * @return array<int, Client>
             */
            public function toArray(): array
            {
                return $this->items;
            }
        };
        $captures = [];
        $builder = $this->makeBuilder($result, $captures);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(Client::class)
            ->willReturn($builder);

        $grant = $this->faker->lexify('grant_????????');
        $filter = ClientFilter::create()->addGrantCriteria(new Grant($grant));

        $manager = new ClientManager($documentManager, $dispatcher);

        $resultClients = $manager->list($filter);

        $this->assertSame([$client], $resultClients);
        $this->assertSame([$grant], $captures['all']['grants']);
        $this->assertArrayNotHasKey('redirectUris', $captures['all'] ?? []);
        $this->assertArrayNotHasKey('scopes', $captures['all'] ?? []);
    }

    public function testListWithRedirectUriFilterOnly(): void
    {
        $client = $this->makeClient();
        $result = new class([$client]) {
            /**
             * @param array<int, Client> $items
             */
            public function __construct(private readonly array $items)
            {
            }

            /**
             * @return array<int, Client>
             */
            public function toArray(): array
            {
                return $this->items;
            }
        };
        $captures = [];
        $builder = $this->makeBuilder($result, $captures);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(Client::class)
            ->willReturn($builder);

        $redirectUri = $this->faker->url();
        $filter = ClientFilter::create()
            ->addRedirectUriCriteria(new RedirectUri($redirectUri));

        $manager = new ClientManager($documentManager, $dispatcher);

        $resultClients = $manager->list($filter);

        $this->assertSame([$client], $resultClients);
        $this->assertSame([$redirectUri], $captures['all']['redirectUris']);
    }

    private function makeClient(): Client
    {
        return new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
        );
    }
}
