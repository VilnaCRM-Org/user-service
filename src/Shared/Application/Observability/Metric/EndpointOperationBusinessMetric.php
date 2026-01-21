<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;

/**
 * Base class for business metrics with endpoint/operation dimensions.
 *
 * Creates pure Value Objects without service dependencies (DDD compliant).
 */
abstract readonly class EndpointOperationBusinessMetric extends BusinessMetric
{
    final public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: $this->endpoint(),
            operation: $this->operation()
        );
    }

    abstract protected function endpoint(): string;

    abstract protected function operation(): string;
}
