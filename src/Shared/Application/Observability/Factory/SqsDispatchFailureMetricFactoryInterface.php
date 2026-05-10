<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Factory;

use App\Shared\Application\Observability\Metric\SqsDispatchFailureMetric;

interface SqsDispatchFailureMetricFactoryInterface
{
    public function create(string $eventType): SqsDispatchFailureMetric;
}
