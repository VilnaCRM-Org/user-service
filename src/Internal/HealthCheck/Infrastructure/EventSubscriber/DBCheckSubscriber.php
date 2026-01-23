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
        $this->documentManager->getClient()->selectDatabase('admin')->command(['ping' => 1]);
    }
}
