<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

interface MetricDimensionsInterface
{
    public function values(): MetricDimensions;
}
