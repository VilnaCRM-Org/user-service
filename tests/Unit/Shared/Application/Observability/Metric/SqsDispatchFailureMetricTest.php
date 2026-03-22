<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\SqsDispatchFailureMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class SqsDispatchFailureMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new SqsDispatchFailureMetric(
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        self::assertSame('SqsDispatchFailures', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new SqsDispatchFailureMetric(
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        $dimensions = $metric->dimensions()->values();

        self::assertSame('EventBus', $dimensions->get('Endpoint'));
        self::assertSame('dispatch', $dimensions->get('Operation'));
        self::assertSame('CustomerCreatedEvent', $dimensions->get('EventType'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new SqsDispatchFailureMetric(
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new SqsDispatchFailureMetric(
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent',
            5
        );

        self::assertSame(5, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new SqsDispatchFailureMetric(
            'App\Core\Customer\Domain\Event\CustomerCreatedEvent'
        );

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
