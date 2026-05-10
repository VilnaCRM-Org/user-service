<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Metric\UsersUpdatedMetric;

final class UsersUpdatedMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new UsersUpdatedMetric();

        self::assertSame('UsersUpdated', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new UsersUpdatedMetric();

        $dimensions = $metric->dimensions()->values();

        self::assertSame('User', $dimensions->get('Endpoint'));
        self::assertSame('update', $dimensions->get('Operation'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new UsersUpdatedMetric();

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new UsersUpdatedMetric(3);

        self::assertSame(3, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new UsersUpdatedMetric();

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
