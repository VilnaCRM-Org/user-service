<?php

namespace App\Tests\Unit\Shared\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\OpenApi\Builder\ContextBuilder;
use App\Shared\OpenApi\Builder\Parameter;
use App\Shared\OpenApi\Builder\RequestBuilder;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

class RequestBuilderTest extends UnitTestCase
{
    private RequestBuilder $builder;
    private ContextBuilder $contextBuilderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextBuilderMock = $this->createMock(ContextBuilder::class);
        $this->builder = new RequestBuilder($this->contextBuilderMock);
    }

    public function testBuildWithEmptyParams(): void
    {
        $this->contextBuilderMock->expects($this->once())
            ->method('build')
            ->with([])
            ->willReturn(new ArrayObject([]));

        $requestBody = $this->builder->build();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }

    public function testBuildWithSimpleParams(): void
    {
        $name = $this->faker->name();
        $age = $this->faker->numberBetween(1, 100);

        $params = [
            new Parameter('name', 'string', $name),
            new Parameter('age', 'integer', $age),
        ];

        $expectedContent = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'age' => ['type' => 'integer'],
                    ],
                ],
                'example' => [
                    'name' => $name,
                    'age' => $age,
                ],
            ],
        ]);

        $this->contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params)
            ->willReturn($expectedContent);

        $requestBody = $this->builder->build($params);

        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertEquals($expectedContent, $requestBody->getContent());
    }
}
