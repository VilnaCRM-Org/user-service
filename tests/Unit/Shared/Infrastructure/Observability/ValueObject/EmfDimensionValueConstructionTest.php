<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;

final class EmfDimensionValueConstructionTest extends UnitTestCase
{
    public function testCreatesWithKeyAndValue(): void
    {
        $dimension = new EmfDimensionValue('Endpoint', 'Customer');

        self::assertSame('Endpoint', $dimension->key());
        self::assertSame('Customer', $dimension->value());
    }
}
