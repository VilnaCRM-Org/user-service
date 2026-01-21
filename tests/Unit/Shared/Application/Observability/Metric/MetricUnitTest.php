<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;

final class MetricUnitTest extends UnitTestCase
{
    public function testValueReturnsUnitString(): void
    {
        $unit = new MetricUnit(MetricUnit::COUNT);

        self::assertSame(MetricUnit::COUNT, $unit->value());
    }

    public function testThrowsForInvalidUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MetricUnit('invalid');
    }
}
