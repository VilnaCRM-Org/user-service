<?php

declare(strict_types=1);

namespace App\Tests\Unit\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Internal\HealthCheck\Infrastructure\EventSubscriber\DBCheckSubscriber;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Client;
use MongoDB\Database;

final class DBCheckSubscriberTest extends UnitTestCase
{
    private DocumentManager $documentManager;
    private DBCheckSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->subscriber = new DBCheckSubscriber(
            $this->documentManager
        );
    }

    public function testOnHealthCheck(): void
    {
        $client = $this->createMock(Client::class);
        $database = $this->createMock(Database::class);
        $configuration = $this->createMock(\Doctrine\ODM\MongoDB\Configuration::class);
        $dbName = $this->faker->word();

        $this->documentManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $configuration->expects($this->once())
            ->method('getDefaultDB')
            ->willReturn($dbName);

        $this->documentManager->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $client->expects($this->once())
            ->method('selectDatabase')
            ->with($dbName)
            ->willReturn($database);

        $database->expects($this->once())->method('command')->with(['ping' => 1]);

        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [HealthCheckEvent::class => 'onHealthCheck'],
            DBCheckSubscriber::getSubscribedEvents()
        );
    }
}
