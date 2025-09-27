<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use App\Shared\Application\OpenApi\Processor\PathParameterCleaner;
use App\Tests\Unit\UnitTestCase;

final class PathParameterCleanerTest extends UnitTestCase
{
    public function testCleanLeavesNonOpenApiParametersUntouched(): void
    {
        $cleaner = new PathParameterCleaner();
        $value = ['not-a-parameter'];

        $this->assertSame($value, $cleaner->clean($value));
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

        $cleaner = new PathParameterCleaner();

        $this->assertSame($parameter, $cleaner->clean($parameter));
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

        $cleaner = new PathParameterCleaner();
        $result = $cleaner->clean($parameter);

        $this->assertNull($result->getAllowEmptyValue());
        $this->assertNull($result->getAllowReserved());
    }
}
