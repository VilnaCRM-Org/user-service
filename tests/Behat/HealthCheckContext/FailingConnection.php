<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

final class FailingConnection extends Connection
{
    /**
     * @param array<int, mixed> $params
     * @param array<int, int|string|null> $types
     */
    #[\Override]
    public function executeQuery(
        string $sql,
        array $params = [],
        array $types = [],
        ?QueryCacheProfile $qcp = null
    ): Result {
        throw new \RuntimeException('Database is not available');
    }

    /**
     * @param array<int, mixed> $params
     * @param array<int, int|string|null> $types
     */
    #[\Override]
    public function executeStatement(
        string $sql,
        array $params = [],
        array $types = []
    ): int {
        throw new \RuntimeException('Database is not available');
    }
}
