<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Transformer\PaginationOperationTransformer;
use App\Shared\Application\OpenApi\Transformer\PaginationParameterTransformer;
use App\Shared\Application\OpenApi\Transformer\PaginationQueryParametersTransformer;
use App\Tests\Unit\UnitTestCase;

final class PaginationQueryParametersTransformerTest extends UnitTestCase
{
    public function testEnforcesPaginationConstraints(): void
    {
        $openApi = $this->createOpenApiWithPaginationParameters();
        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertPaginationConstraintsEnforced($transformed);
    }

    public function testTransformLeavesNonPaginationParametersUntouched(): void
    {
        $filterParameter = $this->createFilterParameter();
        $openApi = $this->createOpenApiWithParameter($filterParameter);
        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertNonPaginationParameterUntouched($transformed);
    }

    public function testTransformSkipsWhenParametersCollectionIsNotArray(): void
    {
        $operation = new Operation(parameters: null);
        $paths = new Paths();
        $paths->addPath('/users', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertSame(
            $operation,
            $transformed->getPaths()->getPath('/users')->getGet()
        );
    }

    public function testTransformLeavesNonOpenApiParametersUntouched(): void
    {
        $operation = new Operation(parameters: ['unexpected']);
        $paths = new Paths();
        $paths->addPath('/users', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $transformed = $this->createTransformer()->transform($openApi);

        $parameters = $transformed->getPaths()
            ->getPath('/users')
            ->getGet()
            ->getParameters();

        $this->assertSame('unexpected', $parameters[0]);
    }

    public function testTransformLeavesNonQueryParametersUntouched(): void
    {
        $headerParameter = $this->createHeaderParameter('X-Header', 'A header parameter');
        $openApi = $this->createOpenApiWithParameter($headerParameter);
        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertHeaderParameterUntouched($transformed);
    }

    public function testPaginationParametersOutsideQueryAreIgnored(): void
    {
        $headerParameter = $this->createPaginationHeaderParameter();
        $openApi = $this->createOpenApiWithParameter($headerParameter);
        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertPaginationHeaderParameterUntouched($transformed);
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

    private function assertPaginationConstraintsEnforced(OpenApi $transformed): void
    {
        $parameters = $transformed->getPaths()->getPath('/users')->getGet()->getParameters();

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

    private function assertNonPaginationParameterUntouched(OpenApi $transformed): void
    {
        $parameter = $transformed->getPaths()->getPath('/users')->getGet()->getParameters()[0];

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

    private function assertHeaderParameterUntouched(OpenApi $transformed): void
    {
        $parameter = $transformed->getPaths()->getPath('/users')->getGet()->getParameters()[0];

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

    private function assertPaginationHeaderParameterUntouched(OpenApi $transformed): void
    {
        $parameter = $transformed->getPaths()->getPath('/users')->getGet()->getParameters()[0];

        $this->assertTrue($parameter->getAllowEmptyValue());
        $this->assertArrayNotHasKey('minimum', $parameter->getSchema());
    }

    private function createTransformer(): PaginationQueryParametersTransformer
    {
        return new PaginationQueryParametersTransformer(
            new PaginationOperationTransformer(new PaginationParameterTransformer())
        );
    }
}
