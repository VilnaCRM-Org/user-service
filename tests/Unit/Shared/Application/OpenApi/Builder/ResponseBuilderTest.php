<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Header;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class ResponseBuilderTest extends UnitTestCase
{
    private ResponseBuilder $builder;
    private ContextBuilder $contextBuilderMock;
    private string $description;
    private string $paramName;
    private string $paramType;
    private string $paramExample;
    private string $headerName;
    private string $headerDescription;
    private string $headerType;
    private string $headerFormat;
    private string $headerExample;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->contextBuilderMock =
            $this->createMock(ContextBuilder::class);
        $this->builder = new ResponseBuilder($this->contextBuilderMock);
        $this->description = $this->faker->sentence();
        $this->paramName = $this->faker->word();
        $this->paramType = $this->faker->word();
        $this->paramExample = $this->faker->word();
        $this->headerName = $this->faker->word();
        $this->headerDescription = $this->faker->sentence();
        $this->headerType = $this->faker->word();
        $this->headerFormat = $this->faker->word();
        $this->headerExample = $this->faker->word();
    }

    public function testBuildWithMinimalData(): void
    {
        $this->contextBuilderMock->expects($this->once())
            ->method('build')
            ->with([], 'application/json')
            ->willReturn(new \ArrayObject([]));

        $response = $this->builder->build($this->description, [], []);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($this->description, $response->getDescription());
        $this->assertEquals(new \ArrayObject([]), $response->getHeaders());
    }

    public function testBuildWithParamsAndHeaders(): void
    {
        $params = $this->getParams();

        $headers = $this->getHeaders();

        $expectedContent = $this->getExpectedContent();

        $expectedHeaders = $this->getExpectedHeaders();

        $this->contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params, 'application/json')
            ->willReturn($expectedContent);

        $response =
            $this->builder->build($this->description, $params, $headers);
        $this->assertEquals($this->description, $response->getDescription());
        $this->assertEquals($expectedContent, $response->getContent());
        $this->assertEquals($expectedHeaders, $response->getHeaders());
    }

    /**
     * @psalm-return \ArrayObject<'application/json', array{schema: array{type: 'object', properties: array<string, array{type: string}>}, example: array<string, string>}>
     */
    private function getExpectedContent(): \ArrayObject
    {
        return new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        $this->paramName => ['type' => $this->paramExample],
                    ],
                ],
                'example' => [
                    $this->paramName => $this->paramExample,
                ],
            ],
        ]);
    }

    /**
     * @return Parameter[]
     *
     * @psalm-return list{Parameter}
     */
    private function getParams(): array
    {
        return [
            new Parameter(
                $this->paramName,
                $this->paramType,
                $this->paramExample
            ),
        ];
    }

    /**
     * @return Header[]
     *
     * @psalm-return list{Header}
     */
    private function getHeaders(): array
    {
        return [
            new Header(
                $this->headerName,
                $this->headerDescription,
                $this->headerType,
                $this->headerFormat,
                $this->headerExample
            ),
        ];
    }

    /**
     * @psalm-return \ArrayObject<string, array{description: string, schema: array{type: string, format?: string}, example?: string}>
     */
    private function getExpectedHeaders(): \ArrayObject
    {
        $schema = ['type' => $this->headerType];

        if ($this->headerFormat !== '') {
            $schema['format'] = $this->headerFormat;
        }

        $header = [
            'description' => $this->headerDescription,
            'schema' => $schema,
        ];

        if ($this->headerExample !== '') {
            $header['example'] = $this->headerExample;
        }

        return new \ArrayObject([
            $this->headerName => $header,
        ]);
    }
}
