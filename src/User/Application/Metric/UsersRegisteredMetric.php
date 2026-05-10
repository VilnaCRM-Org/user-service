<?php

declare(strict_types=1);

namespace App\User\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Metric for tracking user registration events.
 *
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class UsersRegisteredMetric extends EndpointOperationBusinessMetric
{
    private const ENDPOINT = 'User';
    private const OPERATION = 'create';

    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    /**
     * @psalm-return 'UsersRegistered'
     */
    #[\Override]
    public function name(): string
    {
        return 'UsersRegistered';
    }

    /**
     * @psalm-return 'User'
     */
    #[\Override]
    protected function endpoint(): string
    {
        return self::ENDPOINT;
    }

    /**
     * @psalm-return 'create'
     */
    #[\Override]
    protected function operation(): string
    {
        return self::OPERATION;
    }
}
