<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\EventSubscriberFailureMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class EventSubscriberFailureMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new EventSubscriberFailureMetric(
            'App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber',
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        self::assertSame('EventSubscriberFailures', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new EventSubscriberFailureMetric(
            'App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber',
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        $dimensions = $metric->dimensions()->values();

        self::assertSame('EventBus', $dimensions->get('Endpoint'));
        self::assertSame('subscribe', $dimensions->get('Operation'));
        self::assertSame('CustomerCreatedMetricsSubscriber', $dimensions->get('Subscriber'));
        self::assertSame('CustomerCreatedEvent', $dimensions->get('EventType'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new EventSubscriberFailureMetric(
            'App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber',
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new EventSubscriberFailureMetric(
            'App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber',
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent',
            3
        );

        self::assertSame(3, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new EventSubscriberFailureMetric(
            'App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber',
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
