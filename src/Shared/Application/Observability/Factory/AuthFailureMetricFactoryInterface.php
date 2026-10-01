<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Factory;

use App\Shared\Application\Observability\Metric\AuthFailureMetric;

interface AuthFailureMetricFactoryInterface
{
    public function create(string $backend): AuthFailureMetric;
}
