<?php

declare(strict_types=1);

namespace App\User\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Metric for tracking user update events.
 *
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class UsersUpdatedMetric extends EndpointOperationBusinessMetric
{
    private const ENDPOINT = 'User';
    private const OPERATION = 'update';

    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    #[\Override]
    public function name(): string
    {
        return 'UsersUpdated';
    }

    #[\Override]
    protected function endpoint(): string
    {
        return self::ENDPOINT;
    }

    #[\Override]
    protected function operation(): string
    {
        return self::OPERATION;
    }
}
