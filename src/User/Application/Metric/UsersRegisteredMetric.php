<?php

declare(strict_types=1);

namespace App\User\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

final readonly class UsersRegisteredMetric extends EndpointOperationBusinessMetric
{
    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    #[\Override]
    public function name(): string
    {
        return 'UsersRegistered';
    }

    #[\Override]
    protected function endpoint(): string
    {
        return 'User';
    }

    #[\Override]
    protected function operation(): string
    {
        return 'create';
    }
}
