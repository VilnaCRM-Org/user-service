<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\ArraySchemaFactory;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ParameterSchemaFactory;
use App\Shared\Application\OpenApi\Extractor\ArrayExampleValueExtractor;
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
        $factory = $this->createFactory();
        $parameter = new Parameter('email', 'string', 'a@example.com');

        $schema = $factory->create($parameter);

        $this->assertSame(['type' => 'string'], $schema);
    }

    public function testScalarParameterIncludesPatternWhenProvided(): void
    {
        $factory = $this->createFactory();
        $parameter = new Parameter(
            'initials',
            'string',
            'NameSurname',
            pattern: '^\\S+$'
        );

        $schema = $factory->create($parameter);

        $this->assertSame(
            ['type' => 'string', 'pattern' => '^\\S+$'],
            $schema
        );
    }

    public function testScalarParameterIncludesEnumWhenProvided(): void
    {
        $factory = $this->createFactory();
        $enum = [SchemathesisFixtures::CONFIRMATION_TOKEN];
        $parameter = new Parameter(
            'token',
            'string',
            SchemathesisFixtures::CONFIRMATION_TOKEN,
            enum: $enum
        );

        $schema = $factory->create($parameter);

        $this->assertSame(
            ['type' => 'string', 'enum' => $enum],
            $schema
        );
    }

    private function createFactory(): ParameterSchemaFactory
    {
        return new ParameterSchemaFactory(
            new ArraySchemaFactory(new ArrayExampleValueExtractor())
        );
    }
}
