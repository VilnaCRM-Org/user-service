<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Metric\PasswordResetRequestsMetric;

final class PasswordResetRequestsMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new PasswordResetRequestsMetric();

        self::assertSame('PasswordResetRequests', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new PasswordResetRequestsMetric();

        $dimensions = $metric->dimensions()->values();

        self::assertSame('User', $dimensions->get('Endpoint'));
        self::assertSame('request-password-reset', $dimensions->get('Operation'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new PasswordResetRequestsMetric();

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new PasswordResetRequestsMetric(2);

        self::assertSame(2, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new PasswordResetRequestsMetric();

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
