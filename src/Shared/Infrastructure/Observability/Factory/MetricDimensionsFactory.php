<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Factory\MetricDimensionsFactoryInterface;
use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;

final class MetricDimensionsFactory implements MetricDimensionsFactoryInterface
{
    #[\Override]
    public function endpointOperation(string $endpoint, string $operation): MetricDimensions
    {
        return $this->endpointOperationWith($endpoint, $operation);
    }

    #[\Override]
    public function endpointOperationWith(
        string $endpoint,
        string $operation,
        MetricDimension ...$extra
    ): MetricDimensions {
        return new MetricDimensions(
            new MetricDimension('Endpoint', $endpoint),
            new MetricDimension('Operation', $operation),
            ...$extra
        );
    }
}
