<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Enum\Requirement;
use App\Shared\Application\OpenApi\Extractor\ArrayExampleValueExtractor;
use App\Shared\Application\OpenApi\Factory\Schema\ArraySchemaFactory;
use App\Shared\Application\OpenApi\Factory\Schema\ParameterSchemaFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ContextBuilderTest extends UnitTestCase
{
    private ContextBuilder $contextBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $arraySchemaFactory = new ArraySchemaFactory(
            new ArrayExampleValueExtractor()
        );
        $parameterSchemaFactory = new ParameterSchemaFactory(
            $arraySchemaFactory
        );
        $this->contextBuilder = new ContextBuilder($parameterSchemaFactory);
    }

    public function testBuildWithEmptyParams(): void
    {
        $content = $this->contextBuilder->build([]);

        $this->assertEquals(
            new ArrayObject([
                'application/json' => [
                    'example' => new ArrayObject(),
                ],
            ]),
            $content
        );
    }

    public function testBuildWithSimpleParams(): void
    {
        $params = $this->testBuildWithSimpleParamsGetParams();

        $content = $this->contextBuilder->build($params);

        $expectedSchema = $this->buildWithSimpleParamsGetExpectedSchema();

        $expectedExample = [
            'name' => $params[0]->example,
            'age' => $params[1]->example,
        ];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithNestedArrays(): void
    {
        $address = [
            'street' => $this->faker->streetName(),
            'city' => $this->faker->city(),
        ];

        $params = [new Parameter('address', 'object', $address)];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = $this->buildWithNestedArraysGetExpectedSchema();

        $expectedExample = ['address' => $address];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithFormattedParam(): void
    {
        $email = 'user@example.com';
        $params = [new Parameter('email', 'string', $email, 255, 'email')];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = $this->buildEmailSchema();
        $expected = $this->getExpectedResult($expectedSchema, ['email' => $email]);
        $this->assertEquals($expected, $content);
    }

    public function testBuildOmitsRequiredKeyWhenNoParametersRequired(): void
    {
        $params = [
            new Parameter(
                'notes',
                'string',
                $this->faker->sentence(),
                null,
                null,
                Requirement::OPTIONAL
            ),
        ];

        $content = $this->contextBuilder->build($params);
        $schema = $content['application/json']['schema'];

        $this->assertArrayNotHasKey('required', $schema);
    }

    public function testInjectedSchemaFactoryIsUsed(): void
    {
        $parameter = new Parameter('email', 'string', 'value');

        $schemaFactory = $this->createMock(ParameterSchemaFactory::class);
        $schemaFactory->expects($this->once())
            ->method('create')
            ->with($parameter)
            ->willReturn(['type' => 'string', 'format' => 'email']);

        $builder = new ContextBuilder($schemaFactory);
        $content = $builder->build([$parameter]);

        $schema = $content['application/json']['schema'];

        $this->assertSame(
            ['type' => 'string', 'format' => 'email'],
            $schema['properties']['email']
        );
    }

    /**
     * @return array<array<array<int|string>|string>|string>
     *
     * @psalm-return array{type: 'object', properties: array{email: array{type: 'string', maxLength: 255, format: 'email'}}, required: list{'email'}}
     */
    private function buildEmailSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string', 'maxLength' => 255, 'format' => 'email'],
            ],
            'required' => ['email'],
        ];
    }

    /**
     * @return array<array<string|array<string>>|string>
     *
     * @psalm-return array{type: 'object', properties: array{name: array{type: 'string'}, age: array{type: 'integer'}}, required: list{'name', 'age'}}
     */
    private function buildWithSimpleParamsGetExpectedSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'integer',
                ],
            ],
            'required' => ['name', 'age'],
        ];
    }

    /**
     * @return array<array<string|array<string>>|string>
     *
     * @psalm-return array{type: 'object', properties: array{address: array{type: 'object'}}, required: list{'address'}}
     */
    private function buildWithNestedArraysGetExpectedSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'address' => [
                    'type' => 'object',
                ],
            ],
            'required' => ['address'],
        ];
    }

    /**
     * @param array<string,string|array<string>> $expectedSchema
     * @param array<string,string|array<string>> $expectedExample
     *
     * @psalm-return ArrayObject<'application/json', array{schema: array<string, array<string>|string>, example: array<string, array<string>|string>}>
     */
    private function getExpectedResult(
        array $expectedSchema,
        array $expectedExample
    ): ArrayObject {
        return new ArrayObject([
            'application/json' => [
                'schema' => $expectedSchema,
                'example' => $expectedExample,
            ],
        ]);
    }

    /**
     * @return Parameter[]
     *
     * @psalm-return list{Parameter, Parameter}
     */
    private function testBuildWithSimpleParamsGetParams(): array
    {
        return [
            new Parameter(
                'name',
                'string',
                $this->faker->name()
            ),
            new Parameter(
                'age',
                'integer',
                $this->faker->numberBetween(1, 10)
            ),
        ];
    }
}
