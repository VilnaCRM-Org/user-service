<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Header as ApiPlatformHeader;
use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Builder\Header;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Tests\Unit\UnitTestCase;

class ResponseBuilderTest extends UnitTestCase
{
    private ResponseBuilder $builder;
    private ContextBuilder $contextBuilderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextBuilderMock = $this->createMock(ContextBuilder::class);
        $this->builder = new ResponseBuilder($this->contextBuilderMock);
    }

    public function testBuildWithMinimalData(): void
    {
        $description = $this->faker->sentence();

        $this->contextBuilderMock->expects($this->once())
            ->method('build')
            ->with([])
            ->willReturn(new \ArrayObject([]));

        $response = $this->builder->build($description, [], []);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($description, $response->getDescription());
        $this->assertEquals(new \ArrayObject([]), $response->getHeaders());
    }

    public function testBuildWithParamsAndHeaders(): void
    {
        $description = $this->faker->sentence();
        $paramName = $this->faker->word();
        $paramType = $this->faker->word();
        $paramExample = $this->faker->word();
        $params = [
            new Parameter($paramName, $paramType, $paramExample),
        ];

        $headerName = $this->faker->word();
        $headerDescription = $this->faker->sentence();
        $headerType = $this->faker->word();
        $headerFormat = $this->faker->word();
        $headerExample = $this->faker->word();
        $headers = [
            new Header($headerName, $headerDescription, $headerType, $headerFormat, $headerExample),
        ];

        $expectedContent = new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        $paramName => ['type' => $paramExample],
                    ],
                ],
                'example' => [
                    $paramName => $paramExample,
                ],
            ],
        ]);

        $expectedHeaders = new \ArrayObject([
            $headerName => new ApiPlatformHeader(
                description: $headerDescription,
                schema: [
                    'type' => $headerType,
                    'format' => $headerFormat,
                    'example' => $headerExample
                ],
            ),
        ]);

        $contextBuilderMock = $this->createMock(ContextBuilder::class);
        $contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params)
            ->willReturn($expectedContent);

        $builder = new ResponseBuilder($contextBuilderMock);

        $response = $builder->build($description, $params, $headers);
        $this->assertEquals($description, $response->getDescription());
        $this->assertEquals($expectedContent, $response->getContent());
        $this->assertEquals($expectedHeaders, $response->getHeaders());
    }
}
