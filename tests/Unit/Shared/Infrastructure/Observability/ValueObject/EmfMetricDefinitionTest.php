<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Tests\Unit\UnitTestCase;

final class EmfMetricDefinitionTest extends UnitTestCase
{
    public function testCreatesDefinitionWithNameAndUnit(): void
    {
        $definition = new EmfMetricDefinition('CustomersCreated', 'Count');

        self::assertSame('CustomersCreated', $definition->name());
        self::assertSame('Count', $definition->unit());
    }

    public function testSerializesToExpectedJsonStructure(): void
    {
        $definition = new EmfMetricDefinition('OrderValue', 'None');

        $json = $definition->jsonSerialize();

        self::assertSame(['Name' => 'OrderValue', 'Unit' => 'None'], $json);
    }
}
