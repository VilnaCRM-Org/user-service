<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;
use App\Shared\Application\OpenApi\Transformer\PathParametersTransformer;
use App\Tests\Unit\UnitTestCase;

final class PathParametersTransformerTest extends UnitTestCase
{
    public function testTransformRemovesUnsupportedFlagsFromPathParameters(): void
    {
        $openApi = $this->createOpenApiWithPathParameter();
        $transformer = new PathParametersTransformer(new PathParameterCleaner());
        $transformed = $transformer->transform($openApi);

        $this->assertPathParameterWasTransformed($transformed);
    }

    public function testTransformUsesInjectedCleaner(): void
    {
        $parameter = new Parameter(
            name: 'id',
            in: 'path',
            description: 'Identifier',
            required: true,
            schema: ['type' => 'string']
        );

        $operation = new Operation(parameters: [$parameter]);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/users/{id}', $pathItem);

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $cleaner = $this->createMock(PathParameterCleaner::class);
        $cleaner->expects($this->once())
            ->method('clean')
            ->with($parameter)
            ->willReturn($parameter);

        $transformer = new PathParametersTransformer($cleaner);
        $transformer->transform($openApi);
    }

    public function testTransformOperationWithNonArrayParametersReturnsOperation(): void
    {
        $operation = new Operation();

        $transformer = new PathParametersTransformer();

        $method = new \ReflectionMethod(PathParametersTransformer::class, 'transformOperation');
        $this->makeAccessible($method);

        $result = $method->invoke($transformer, $operation);

        $this->assertSame($operation, $result);
    }

    public function testTransformOperationReturnsNullForMissingOperation(): void
    {
        $transformer = new PathParametersTransformer();

        $method = new \ReflectionMethod(PathParametersTransformer::class, 'transformOperation');
        $this->makeAccessible($method);

        $this->assertNull($method->invoke($transformer, null));
    }

    private function createOpenApiWithPathParameter(): OpenApi
    {
        $parameter = $this->createPathParameterWithUnsupportedFlags();
        $operation = new Operation(parameters: [$parameter]);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/resource/{id}', $pathItem);

        return new OpenApi(new Info('Test', '1.0.0'), [], $paths);
    }

    private function createPathParameterWithUnsupportedFlags(): Parameter
    {
        return new Parameter(
            name: 'id',
            in: 'path',
            description: 'Identifier',
            required: true,
            allowEmptyValue: true,
            schema: ['type' => 'string'],
            allowReserved: true
        );
    }

    private function assertPathParameterWasTransformed(OpenApi $transformed): void
    {
        $transformedParameter = $transformed->getPaths()
            ->getPath('/resource/{id}')
            ->getGet()
            ->getParameters()[0];

        $this->assertNull($transformedParameter->getAllowEmptyValue());
        $this->assertNull($transformedParameter->getAllowReserved());
    }
}
