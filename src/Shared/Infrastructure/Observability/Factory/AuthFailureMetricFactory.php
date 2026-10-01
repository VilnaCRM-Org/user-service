<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Factory\AuthFailureMetricFactoryInterface;
use App\Shared\Application\Observability\Metric\AuthFailureMetric;

final readonly class AuthFailureMetricFactory implements AuthFailureMetricFactoryInterface
{
    #[\Override]
    public function create(string $backend): AuthFailureMetric
    {
        return new AuthFailureMetric(backend: $backend);
    }
}
