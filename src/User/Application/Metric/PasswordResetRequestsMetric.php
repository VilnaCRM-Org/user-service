<?php

declare(strict_types=1);

namespace App\User\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

final readonly class PasswordResetRequestsMetric extends EndpointOperationBusinessMetric
{
    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'PasswordResetRequests';
    }

    protected function endpoint(): string
    {
        return 'User';
    }

    protected function operation(): string
    {
        return 'request-password-reset';
    }
}
