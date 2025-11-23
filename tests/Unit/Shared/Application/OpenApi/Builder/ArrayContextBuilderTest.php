<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ArrayContextBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\Requirement;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ArrayContextBuilderTest extends UnitTestCase
{
    private ArrayContextBuilder $contextBuilder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->contextBuilder = new ArrayContextBuilder();
    }

    public function testBuildWithEmptyParams(): void
    {
        $content = $this->contextBuilder->build([]);

        $this->assertEquals(
            new ArrayObject([
                'application/json' => [
                    'example' => [],
                    'schema' => [
                        'type' => 'array',
                        'items' => ['type' => 'object'],
                    ],
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
            [
                'name' => $params[0]->example,
                'age' => $params[1]->example,
            ],
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

        $expectedExample = [
            ['address' => $address],
        ];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildOmitsRequiredWhenParametersOptional(): void
    {
        $optionalParam = new Parameter(
            'notes',
            'string',
            $this->faker->sentence(),
            null,
            null,
            Requirement::OPTIONAL
        );

        $content = $this->contextBuilder->build([$optionalParam]);
        $schema = $content['application/json']['schema'];

        $this->assertArrayNotHasKey('required', $schema['items']);
    }

    /**
     * @return  array<string,string|array<string>>
     */
    private function buildWithSimpleParamsGetExpectedSchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'maxLength' => 255,
                        'format' => 'uuid',
                    ],
                    'age' => [
                        'type' => 'integer',
                    ],
                ],
                'required' => ['name', 'age'],
            ],
        ];
    }

    /**
     * @return  array<string,string|array<string>>
     */
    private function buildWithNestedArraysGetExpectedSchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'address' => [
                        'type' => 'object',
                    ],
                ],
                'required' => ['address'],
            ],
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
                $this->faker->name(),
                255,
                'uuid'
            ),
            new Parameter(
                'age',
                'integer',
                $this->faker->numberBetween(1, 10)
            ),
        ];
    }
}
