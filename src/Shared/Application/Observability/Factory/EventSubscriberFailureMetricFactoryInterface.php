<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Factory;

use App\Shared\Application\Observability\Metric\EventSubscriberFailureMetric;

interface EventSubscriberFailureMetricFactoryInterface
{
    public function create(
        string $subscriberClass,
        string $eventType
    ): EventSubscriberFailureMetric;
}
