<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Factory;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;

interface MetricDimensionsFactoryInterface
{
    public function endpointOperation(string $endpoint, string $operation): MetricDimensions;

    public function endpointOperationWith(
        string $endpoint,
        string $operation,
        MetricDimension ...$extra
    ): MetricDimensions;
}
