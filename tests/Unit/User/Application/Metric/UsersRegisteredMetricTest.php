<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Metric\UsersRegisteredMetric;

final class UsersRegisteredMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new UsersRegisteredMetric();

        self::assertSame('UsersRegistered', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new UsersRegisteredMetric();

        $dimensions = $metric->dimensions()->values();

        self::assertSame('User', $dimensions->get('Endpoint'));
        self::assertSame('create', $dimensions->get('Operation'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new UsersRegisteredMetric();

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new UsersRegisteredMetric(5);

        self::assertSame(5, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new UsersRegisteredMetric();

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
