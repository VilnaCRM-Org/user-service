<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Manager;

use App\OAuth\Infrastructure\Manager\ClientManager;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Event\PreSaveClientEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientManagerTest extends UnitTestCase
{
    use BuilderMockFactoryTrait;

    public function testSaveDispatchesEventAndPersistsClient(): void
    {
        $client = $this->makeClient();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PreSaveClientEvent::class), OAuth2Events::PRE_SAVE_CLIENT)
            ->willReturnCallback(static fn (PreSaveClientEvent $event): PreSaveClientEvent => $event);

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

    public function testListAppliesFiltersAndReturnsClients(): void
    {
        $client = $this->makeClient();
        $result = new class ([5 => $client]) {
            public function __construct(private readonly array $items)
            {
            }

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

        $filter = ClientFilter::create()
            ->addGrantCriteria(new Grant('authorization_code'))
            ->addRedirectUriCriteria(new RedirectUri('https://example.com/callback'))
            ->addScopeCriteria(new Scope('email'));

        $manager = new ClientManager($documentManager, $dispatcher);

        $resultClients = $manager->list($filter);

        $this->assertSame([$client], $resultClients);
        $this->assertSame(['authorization_code'], $captures['all']['grants']);
        $this->assertSame(['https://example.com/callback'], $captures['all']['redirectUris']);
        $this->assertSame(['email'], $captures['all']['scopes']);
    }

    private function makeClient(): Client
    {
        return new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->optional()->sha1()
        );
    }
}
