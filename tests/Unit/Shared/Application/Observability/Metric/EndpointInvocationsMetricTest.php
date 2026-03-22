<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class EndpointInvocationsMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new EndpointInvocationsMetric('Customer', 'create');

        self::assertSame('EndpointInvocations', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new EndpointInvocationsMetric('Customer', '_api_/customers_post');

        $dimensions = $metric->dimensions()->values();

        self::assertSame('Customer', $dimensions->get('Endpoint'));
        self::assertSame('_api_/customers_post', $dimensions->get('Operation'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new EndpointInvocationsMetric('HealthCheck', 'get');

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new EndpointInvocationsMetric('Customer', 'create', 42);

        self::assertSame(42, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new EndpointInvocationsMetric('Customer', 'create');

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
