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
        $openApi = $this->createOpenApiWithPaginationParameters();
        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $this->assertPaginationConstraintsEnforced($sanitized);
    }

    public function testSanitizeLeavesNonPaginationParametersUntouched(): void
    {
        $filterParameter = $this->createFilterParameter();
        $openApi = $this->createOpenApiWithParameter($filterParameter);
        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $this->assertNonPaginationParameterUntouched($sanitized);
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
        $headerParameter = $this->createHeaderParameter('X-Header', 'A header parameter');
        $openApi = $this->createOpenApiWithParameter($headerParameter);
        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $this->assertHeaderParameterUntouched($sanitized);
    }

    public function testPaginationParametersOutsideQueryAreIgnored(): void
    {
        $headerParameter = $this->createPaginationHeaderParameter();
        $openApi = $this->createOpenApiWithParameter($headerParameter);
        $sanitized = $this->createSanitizer()->sanitize($openApi);

        $this->assertPaginationHeaderParameterUntouched($sanitized);
    }

    private function createOpenApiWithPaginationParameters(): OpenApi
    {
        $pageParameter = $this->createPageParameter();
        $itemsParameter = $this->createItemsPerPageParameter();
        $operation = new Operation(parameters: [$pageParameter, $itemsParameter]);

        $paths = new Paths();
        $paths->addPath('/users', (new PathItem())->withGet($operation));

        return new OpenApi(new Info('Test', '1.0.0'), [], $paths);
    }

    private function createPageParameter(): Parameter
    {
        return new Parameter(
            name: 'page',
            in: 'query',
            description: 'The collection page number',
            schema: ['type' => 'integer', 'default' => 1],
            allowEmptyValue: true
        );
    }

    private function createItemsPerPageParameter(): Parameter
    {
        return new Parameter(
            name: 'itemsPerPage',
            in: 'query',
            description: 'The number of items per page',
            schema: ['type' => 'integer', 'default' => 30],
            allowEmptyValue: true
        );
    }

    private function assertPaginationConstraintsEnforced(OpenApi $sanitized): void
    {
        $parameters = $sanitized->getPaths()->getPath('/users')->getGet()->getParameters();

        $this->assertSame(1, $parameters[0]->getSchema()['minimum']);
        $this->assertFalse($parameters[0]->getAllowEmptyValue());

        $this->assertSame(1, $parameters[1]->getSchema()['minimum']);
        $this->assertFalse($parameters[1]->getAllowEmptyValue());
    }

    private function createFilterParameter(): Parameter
    {
        return new Parameter(
            name: 'email',
            in: 'query',
            description: 'Filter by email',
            schema: ['type' => 'string'],
            allowEmptyValue: true
        );
    }

    private function createOpenApiWithParameter(Parameter $parameter): OpenApi
    {
        $paths = new Paths();
        $paths->addPath(
            '/users',
            (new PathItem())->withGet(new Operation(parameters: [$parameter]))
        );

        return new OpenApi(new Info('Test', '1.0.0'), [], $paths);
    }

    private function assertNonPaginationParameterUntouched(OpenApi $sanitized): void
    {
        $parameter = $sanitized->getPaths()->getPath('/users')->getGet()->getParameters()[0];

        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertArrayNotHasKey('minimum', $parameter->getSchema());
    }

    private function createHeaderParameter(string $name, string $description): Parameter
    {
        return new Parameter(
            name: $name,
            in: 'header',
            description: $description,
            schema: ['type' => 'string'],
            allowEmptyValue: true
        );
    }

    private function assertHeaderParameterUntouched(OpenApi $sanitized): void
    {
        $parameter = $sanitized->getPaths()->getPath('/users')->getGet()->getParameters()[0];

        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertSame('header', $parameter->getIn());
    }

    private function createPaginationHeaderParameter(): Parameter
    {
        return new Parameter(
            name: 'page',
            in: 'header',
            description: 'A header based pagination parameter',
            schema: ['type' => 'integer'],
            allowEmptyValue: true
        );
    }

    private function assertPaginationHeaderParameterUntouched(OpenApi $sanitized): void
    {
        $parameter = $sanitized->getPaths()->getPath('/users')->getGet()->getParameters()[0];

        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertArrayNotHasKey('minimum', $parameter->getSchema());
    }

    private function createSanitizer(): PaginationQueryParametersSanitizer
    {
        return new PaginationQueryParametersSanitizer(
            new PaginationOperationSanitizer(new PaginationParameterSanitizer())
        );
    }
}
