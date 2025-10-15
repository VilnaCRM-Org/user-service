<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\PaginationOperationSanitizer;
use App\Shared\Application\OpenApi\Processor\PaginationParameterSanitizer;
use App\Shared\Application\OpenApi\Processor\PaginationQueryParametersSanitizer;
use App\Tests\Unit\UnitTestCase;

final class PaginationQueryParametersSanitizerTest extends UnitTestCase
{
    public function testEnforcesPaginationConstraints(): void
    {
        $pageParameter = new Parameter(
            name: 'page',
            in: 'query',
            description: 'The collection page number',
            schema: ['type' => 'integer', 'default' => 1],
            allowEmptyValue: true
        );

        $itemsParameter = new Parameter(
            name: 'itemsPerPage',
            in: 'query',
            description: 'The number of items per page',
            schema: ['type' => 'integer', 'default' => 30],
            allowEmptyValue: true
        );

        $operation = new Operation(
            parameters: [$pageParameter, $itemsParameter]
        );

        $paths = new Paths();
        $paths->addPath('/users', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $parameters = $sanitized->getPaths()
            ->getPath('/users')
            ->getGet()
            ->getParameters();

        $this->assertSame(1, $parameters[0]->getSchema()['minimum']);
        $this->assertFalse($parameters[0]->getAllowEmptyValue());

        $this->assertSame(1, $parameters[1]->getSchema()['minimum']);
        $this->assertFalse($parameters[1]->getAllowEmptyValue());
    }

    public function testSanitizeLeavesNonPaginationParametersUntouched(): void
    {
        $filterParameter = new Parameter(
            name: 'email',
            in: 'query',
            description: 'Filter by email',
            schema: ['type' => 'string'],
            allowEmptyValue: true
        );

        $paths = new Paths();
        $paths->addPath(
            '/users',
            (new PathItem())->withGet(
                new Operation(parameters: [$filterParameter])
            )
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $parameter = $sanitized->getPaths()
            ->getPath('/users')
            ->getGet()
            ->getParameters()[0];

        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertArrayNotHasKey('minimum', $parameter->getSchema());
    }

    public function testSanitizeSkipsWhenParametersCollectionIsNotArray(): void
    {
        $operation = new Operation(parameters: null);
        $paths = new Paths();
        $paths->addPath('/users', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $this->assertSame(
            $operation,
            $sanitized->getPaths()->getPath('/users')->getGet()
        );
    }

    public function testSanitizeLeavesNonOpenApiParametersUntouched(): void
    {
        $operation = new Operation(parameters: ['unexpected']);
        $paths = new Paths();
        $paths->addPath('/users', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $parameters = $sanitized->getPaths()
            ->getPath('/users')
            ->getGet()
            ->getParameters();

        $this->assertSame('unexpected', $parameters[0]);
    }

    public function testSanitizeLeavesNonQueryParametersUntouched(): void
    {
        $headerParameter = new Parameter(
            name: 'X-Header',
            in: 'header',
            description: 'A header parameter',
            schema: ['type' => 'string'],
            allowEmptyValue: true
        );

        $paths = new Paths();
        $paths->addPath(
            '/users',
            (new PathItem())->withGet(
                new Operation(parameters: [$headerParameter])
            )
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $parameter = $sanitized->getPaths()
            ->getPath('/users')
            ->getGet()
            ->getParameters()[0];

        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertSame('header', $parameter->getIn());
    }

    private function createSanitizer(): PaginationQueryParametersSanitizer
    {
        return new PaginationQueryParametersSanitizer(
            new PaginationOperationSanitizer(new PaginationParameterSanitizer())
        );
    }
}
