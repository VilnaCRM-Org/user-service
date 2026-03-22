<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Metric\UsersUpdatedMetric;

interface UsersUpdatedMetricFactoryInterface
{
    public function create(float|int $value = 1): UsersUpdatedMetric;
}
