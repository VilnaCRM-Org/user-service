<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\PathParameterCleaner;
use App\Shared\Application\OpenApi\Processor\PathParametersSanitizer;
use App\Tests\Unit\UnitTestCase;

final class PathParametersSanitizerTest extends UnitTestCase
{
    public function testSanitizeRemovesUnsupportedFlagsFromPathParameters(): void
    {
        $parameter = new Parameter(
            name: 'id',
            in: 'path',
            description: 'Identifier',
            required: true,
            allowEmptyValue: true,
            schema: ['type' => 'string'],
            allowReserved: true
        );

        $operation = new Operation(parameters: [$parameter]);
        $pathItem = (new PathItem())->withGet($operation);

        $paths = new Paths();
        $paths->addPath('/resource/{id}', $pathItem);

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $sanitizer = new PathParametersSanitizer(new PathParameterCleaner());
        $sanitized = $sanitizer->sanitize($openApi);

        $sanitizedParameter = $sanitized->getPaths()
            ->getPath('/resource/{id}')
            ->getGet()
            ->getParameters()[0];

        $this->assertNull($sanitizedParameter->getAllowEmptyValue());
        $this->assertNull($sanitizedParameter->getAllowReserved());
    }

    public function testSanitizeUsesInjectedCleaner(): void
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

        $sanitizer = new PathParametersSanitizer($cleaner);
        $sanitizer->sanitize($openApi);
    }

    public function testSanitizeOperationWithNonArrayParametersReturnsOperation(): void
    {
        $operation = new Operation();

        $sanitizer = new PathParametersSanitizer();

        $method = new \ReflectionMethod(PathParametersSanitizer::class, 'sanitizeOperation');
        $method->setAccessible(true);

        $result = $method->invoke($sanitizer, $operation);

        $this->assertSame($operation, $result);
    }

    public function testSanitizeOperationReturnsNullForMissingOperation(): void
    {
        $sanitizer = new PathParametersSanitizer();

        $method = new \ReflectionMethod(PathParametersSanitizer::class, 'sanitizeOperation');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($sanitizer, null));
    }
}
