<?php

declare(strict_types=1);

namespace App\Tests\Integration\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Internal\HealthCheck\Infrastructure\EventSubscriber\DBCheckSubscriber;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;

final class DBCheckSubscriberTest extends IntegrationTestCase
{
    private DocumentManager $documentManager;
    private DBCheckSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->container->get(
            'doctrine_mongodb.odm.default_document_manager'
        );
        $this->subscriber = new DBCheckSubscriber($this->documentManager);
    }

    public function testOnHealthCheck(): void
    {
        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);

        $result = $this->documentManager->getClient()
            ->selectDatabase('admin')
            ->command(['ping' => 1]);
        $resultArray = $result->toArray()[0];

        $this->assertEquals(1, $resultArray['ok']);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [HealthCheckEvent::class => 'onHealthCheck'],
            DBCheckSubscriber::getSubscribedEvents()
        );
    }
}
