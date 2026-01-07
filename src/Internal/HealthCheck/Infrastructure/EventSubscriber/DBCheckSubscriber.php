<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Doctrine\DBAL\Connection;

final class DBCheckSubscriber extends BaseHealthCheckSubscriber
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[\Override]
    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $this->connection->executeQuery('SELECT 1');
    }
}
