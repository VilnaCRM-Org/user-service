<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\AuthFailureMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class AuthFailureMetricTest extends UnitTestCase
{
    public function testReturnsAuthFailureMetricName(): void
    {
        $metric = new AuthFailureMetric($this->faker->word());

        self::assertSame('auth_failure', $metric->name());
    }

    public function testUsesBackendAsTheOnlyDimension(): void
    {
        $backend = $this->faker->word();
        $metric = new AuthFailureMetric($backend);

        $dimensions = $metric->dimensions()->values();

        self::assertSame(['backend' => $backend], $dimensions->toAssociativeArray());
    }

    public function testCountsOneFailureByDefault(): void
    {
        $metric = new AuthFailureMetric($this->faker->word());

        self::assertSame(1, $metric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $value = $this->faker->numberBetween(2, 10);

        self::assertSame($value, (new AuthFailureMetric($this->faker->word(), $value))->value());
    }
}
