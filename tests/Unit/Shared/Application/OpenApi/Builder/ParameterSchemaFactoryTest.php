<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ArraySchemaFactory;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ParameterSchemaFactory;
use App\Tests\Unit\UnitTestCase;

final class ParameterSchemaFactoryTest extends UnitTestCase
{
    public function testInjectedArraySchemaFactoryIsUsedForArrayParameters(): void
    {
        $arraySchema = ['type' => 'array', 'items' => ['type' => 'string']];

        $customFactory = $this->createMock(ArraySchemaFactory::class);
        $customFactory->expects($this->once())
            ->method('create')
            ->willReturn($arraySchema);

        $factory = new ParameterSchemaFactory($customFactory);
        $parameter = new Parameter('values', 'array', []);

        $this->assertSame($arraySchema, $factory->create($parameter));
    }

    public function testScalarParameterExcludesNullAttributes(): void
    {
        $factory = new ParameterSchemaFactory();
        $parameter = new Parameter('email', 'string', 'a@example.com');

        $schema = $factory->create($parameter);

        $this->assertSame(['type' => 'string'], $schema);
    }
}
