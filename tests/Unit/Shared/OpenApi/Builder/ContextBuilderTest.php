<?php

namespace App\Tests\Unit\Shared\OpenApi\Builder;

use App\Shared\OpenApi\Builder\ContextBuilder;
use App\Shared\OpenApi\Builder\Parameter;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

class ContextBuilderTest extends UnitTestCase
{
    private ContextBuilder $contextBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextBuilder = new ContextBuilder();
    }

    public function testBuildWithEmptyParams(): void
    {
        $content = $this->contextBuilder->build();

        $this->assertEquals(
            new ArrayObject([
                'application/json' => [
                    'example' => '',
                ],
            ]),
            $content
        );
    }

    public function testBuildWithSimpleParams(): void
    {
        $params = [
            new Parameter('name', 'string', $this->faker->name()),
            new Parameter('age', 'integer', $this->faker->numberBetween(1, 10)),
        ];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ];

        $expectedExample = [
            'name' => $params[0]->example,
            'age' => $params[1]->example,
        ];

        $this->assertEquals(
            new ArrayObject([
                'application/json' => [
                    'schema' => $expectedSchema,
                    'example' => $expectedExample,
                ],
            ]),
            $content
        );
    }

    public function testBuildWithNestedArrays(): void
    {
        $address = [
            'street' => $this->faker->streetName(),
            'city' => $this->faker->city(),
        ];

        $params = [
            new Parameter('address', 'object', $address),
        ];

        $content = $this->contextBuilder->build($params);

        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'address' => [
                    'type' => 'object',
                ],
            ],
        ];

        $expectedExample = [
            'address' => $address,
        ];

        $this->assertEquals(
            new ArrayObject([
                'application/json' => [
                    'schema' => $expectedSchema,
                    'example' => $expectedExample,
                ],
            ]),
            $content
        );
    }
}
