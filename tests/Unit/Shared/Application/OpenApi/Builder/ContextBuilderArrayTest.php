<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Extractor\ArrayExampleValueExtractor;
use App\Shared\Application\OpenApi\Factory\Schema\ArraySchemaFactory;
use App\Shared\Application\OpenApi\Factory\Schema\ParameterSchemaFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ContextBuilderArrayTest extends UnitTestCase
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

    /**
     * @return array<array<array<string|array<string>>|string>|string>
     *
     * @psalm-return array{type: 'object', properties: array<string, array{type: 'array', items: array{type: 'object'}}>, required: list{string}}
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
}
