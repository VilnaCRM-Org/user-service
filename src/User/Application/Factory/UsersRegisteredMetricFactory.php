<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Metric\UsersRegisteredMetric;

final readonly class UsersRegisteredMetricFactory implements UsersRegisteredMetricFactoryInterface
{
    #[\Override]
    public function create(float|int $value = 1): UsersRegisteredMetric
    {
        return new UsersRegisteredMetric($value);
    }
}
