<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Doctrine\ODM\MongoDB\DocumentManager;

final class DBCheckSubscriber extends BaseHealthCheckSubscriber
{
    public function __construct(private readonly DocumentManager $documentManager)
    {
    }

    #[\Override]
    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $dbName = $this->documentManager->getConfiguration()->getDefaultDB();
        $this->documentManager->getClient()->selectDatabase($dbName)->command(['ping' => 1]);
    }
}
