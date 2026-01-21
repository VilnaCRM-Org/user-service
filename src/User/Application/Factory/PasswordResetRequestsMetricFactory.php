<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Metric\PasswordResetRequestsMetric;

final readonly class PasswordResetRequestsMetricFactory implements PasswordResetRequestsMetricFactoryInterface
{
    public function create(float|int $value = 1): PasswordResetRequestsMetric
    {
        return new PasswordResetRequestsMetric($value);
    }
}
