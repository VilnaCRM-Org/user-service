<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use App\Shared\Application\OpenApi\Transformer\PathParameterTransformer;
use App\Tests\Unit\UnitTestCase;

final class PathParameterTransformerTest extends UnitTestCase
{
    public function testCleanLeavesNonOpenApiParametersUntouched(): void
    {
        $parameterTransformer = new PathParameterTransformer();
        $value = ['not-a-parameter'];

        $this->assertSame($value, $parameterTransformer->transform($value));
    }

    public function testCleanSkipsNonPathParameters(): void
    {
        $parameter = new OpenApiParameter(
            name: 'limit',
            in: 'query',
            description: 'Limit results',
            required: false,
            schema: ['type' => 'integer']
        );

        $parameterTransformer = new PathParameterTransformer();

        $this->assertSame($parameter, $parameterTransformer->transform($parameter));
    }

    public function testCleanRemovesUnsupportedFlagsForPathParameters(): void
    {
        $parameter = new OpenApiParameter(
            name: 'id',
            in: 'path',
            description: 'Identifier',
            required: true,
            allowEmptyValue: true,
            schema: ['type' => 'string'],
            allowReserved: true
        );

        $parameterTransformer = new PathParameterTransformer();
        $result = $parameterTransformer->transform($parameter);

        $this->assertNull($result->getAllowEmptyValue());
        $this->assertNull($result->getAllowReserved());
    }
}
