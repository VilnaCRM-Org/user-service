<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ArraySchemaFactory;
use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ParameterSchemaFactory;
use App\Shared\Application\OpenApi\Builder\Requirement;
use App\Shared\Application\OpenApi\Extractor\ArrayExampleValueExtractor;
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

    public function testBuildWithArrayOfObjects(): void
    {
        $example = [['propertyPath' => 'field', 'message' => 'must not be blank']];
        $params = [new Parameter('violations', 'array', $example)];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = $this->buildArrayObjectSchema('violations');
        $expected = $this->getExpectedResult($expectedSchema, ['violations' => $example]);
        $this->assertEquals($expected, $content);
    }

    public function testBuildWithArrayOfScalars(): void
    {
        $example = ['one', 'two'];

        $params = [new Parameter('values', 'array', $example)];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['values'],
        ];

        $expectedExample = ['values' => $example];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithAssociativeArray(): void
    {
        $example = ['first' => ['id' => 1]];

        $params = [new Parameter('payload', 'array', $example)];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'payload' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
            ],
            'required' => ['payload'],
        ];

        $expectedExample = ['payload' => $example];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithEmptyArrayParam(): void
    {
        $params = [new Parameter('values', 'array', [])];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['values'],
        ];

        $expectedExample = ['values' => []];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithNonArrayExampleDefaultsToString(): void
    {
        $params = [new Parameter('values', 'array', 'invalid')];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['values'],
        ];

        $expectedExample = ['values' => 'invalid'];

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
     * @return array<string, string|array<string, string|array<string, string>>>
     */
    private function buildArrayObjectSchema(string $propertyName): array
    {
        return [
            'type' => 'object',
            'properties' => [$propertyName => ['type' => 'array', 'items' => ['type' => 'object']]],
            'required' => [$propertyName],
        ];
    }

    /**
     * @return array<string, string|array<string, string|array<string, string|int>>>
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
     * @return  array<string,string|array<string>>
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
     * @return  array<string,string|array<string>>
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
     * @return array<Parameter>
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
