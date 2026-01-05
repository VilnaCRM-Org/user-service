<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Doctrine\DBAL\Connection;

final class DBCheckSubscriber extends BaseHealthCheckSubscriber
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $this->connection->executeQuery('SELECT 1');
    }
}
