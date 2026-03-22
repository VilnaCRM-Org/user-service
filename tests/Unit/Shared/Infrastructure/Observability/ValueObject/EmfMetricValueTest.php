<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use App\Tests\Unit\UnitTestCase;

final class EmfMetricValueTest extends UnitTestCase
{
    public function testCreatesWithNameAndIntValue(): void
    {
        $value = new EmfMetricValue('CustomersCreated', 5);

        self::assertSame('CustomersCreated', $value->name());
        self::assertSame(5, $value->value());
    }

    public function testCreatesWithNameAndFloatValue(): void
    {
        $value = new EmfMetricValue('OrderValue', 99.99);

        self::assertSame('OrderValue', $value->name());
        self::assertSame(99.99, $value->value());
    }
}
