<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Resolver;

use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Shared\Infrastructure\Observability\Resolver\ApiEndpointMetricDimensionsResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiEndpointMetricDimensionsResolverTest extends UnitTestCase
{
    private ApiEndpointMetricDimensionsResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ApiEndpointMetricDimensionsResolver(
            new MetricDimensionsFactory()
        );
    }

    public function testDimensionsReturnsEndpointAndOperation(): void
    {
        $request = Request::create('/api/customers', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\Customer');
        $request->attributes->set('_api_operation_name', '_api_/customers_get_collection');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('Customer', $dimensions->endpoint());
        self::assertSame('_api_/customers_get_collection', $dimensions->operation());
    }

    public function testEndpointExtractsClassNameFromResourceClass(): void
    {
        $request = Request::create('/api/customer-types', 'POST');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\CustomerType');
        $request->attributes->set('_api_operation_name', '_api_/customer-types_post');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('CustomerType', $dimensions->endpoint());
    }

    public function testEndpointFallsBackToPathWhenNoResourceClass(): void
    {
        $request = Request::create('/api/graphql', 'POST');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('/api/graphql', $dimensions->endpoint());
    }

    public function testEndpointFallsBackToPathWhenResourceClassIsEmpty(): void
    {
        $request = Request::create('/api/health', 'GET');
        $request->attributes->set('_api_resource_class', '');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('/api/health', $dimensions->endpoint());
    }

    public function testOperationUsesOperationNameWhenAvailable(): void
    {
        $request = Request::create('/api/customers/01JCXYZ', 'GET');
        $request->attributes->set('_api_operation_name', '_api_/customers/{ulid}_get');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('_api_/customers/{ulid}_get', $dimensions->operation());
    }

    public function testOperationFallsBackToHttpMethodWhenNoOperationName(): void
    {
        $request = Request::create('/api/customers', 'PATCH');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('patch', $dimensions->operation());
    }

    public function testOperationFallsBackToHttpMethodWhenOperationNameIsEmpty(): void
    {
        $request = Request::create('/api/customers', 'DELETE');
        $request->attributes->set('_api_operation_name', '');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('delete', $dimensions->operation());
    }

    /**
     * @dataProvider httpMethodsProvider
     */
    public function testOperationLowercasesHttpMethod(string $method, string $expected): void
    {
        $request = Request::create('/api/test', $method);

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame($expected, $dimensions->operation());
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function httpMethodsProvider(): iterable
    {
        yield 'GET' => ['GET', 'get'];
        yield 'POST' => ['POST', 'post'];
        yield 'PUT' => ['PUT', 'put'];
        yield 'PATCH' => ['PATCH', 'patch'];
        yield 'DELETE' => ['DELETE', 'delete'];
    }

    public function testHandlesNestedResourceClass(): void
    {
        $request = Request::create('/api/customer-statuses', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\CustomerStatus');
        $request->attributes->set('_api_operation_name', '_api_/customer-statuses_get_collection');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('CustomerStatus', $dimensions->endpoint());
        self::assertSame('_api_/customer-statuses_get_collection', $dimensions->operation());
    }
}
