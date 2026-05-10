<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;

/**
 * Test dimensions that produce invalid UTF-8 for JSON encoding failure tests
 */
final readonly class TestInvalidUtf8Dimensions implements MetricDimensionsInterface
{
    #[\Override]
    public function values(): MetricDimensions
    {
        return new MetricDimensions(
            new MetricDimension('Endpoint', "\xB1"), // Invalid UTF-8
            new MetricDimension('Operation', 'create')
        );
    }
}
