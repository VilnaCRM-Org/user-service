<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\PasswordResetRequestsMetricFactory;
use App\User\Application\Metric\PasswordResetRequestsMetric;

final class PasswordResetRequestsMetricFactoryTest extends UnitTestCase
{
    public function testCreateWithDefaultValue(): void
    {
        $factory = new PasswordResetRequestsMetricFactory();

        $metric = $factory->create();

        self::assertInstanceOf(PasswordResetRequestsMetric::class, $metric);
        self::assertSame(1, $metric->value());
    }

    public function testCreateWithCustomValue(): void
    {
        $factory = new PasswordResetRequestsMetricFactory();

        $metric = $factory->create(5);

        self::assertInstanceOf(PasswordResetRequestsMetric::class, $metric);
        self::assertSame(5, $metric->value());
    }

    public function testCreateWithFloatValue(): void
    {
        $factory = new PasswordResetRequestsMetricFactory();

        $metric = $factory->create(2.5);

        self::assertInstanceOf(PasswordResetRequestsMetric::class, $metric);
        self::assertSame(2.5, $metric->value());
    }
}
