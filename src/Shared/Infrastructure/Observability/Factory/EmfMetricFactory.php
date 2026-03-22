<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;

final readonly class EmfMetricFactory
{
    public function createDefinition(BusinessMetric $metric): EmfMetricDefinition
    {
        return new EmfMetricDefinition($metric->name(), $metric->unit()->value());
    }

    public function createValue(BusinessMetric $metric): EmfMetricValue
    {
        return new EmfMetricValue($metric->name(), $metric->value());
    }
}
