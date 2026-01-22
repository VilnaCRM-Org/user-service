<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Resolver;

use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves API endpoint dimensions from HTTP request.
 *
 * Creates pure Value Objects without service dependencies.
 */
final readonly class ApiEndpointMetricDimensionsResolver
{
    public function dimensions(Request $request): EndpointOperationMetricDimensions
    {
        return new EndpointOperationMetricDimensions(
            endpoint: $this->endpoint($request),
            operation: $this->operation($request)
        );
    }

    private function endpoint(Request $request): string
    {
        $path = $request->getPathInfo();
        $resourceClass = $request->attributes->getString('_api_resource_class', '');
        if ($resourceClass === '') {
            return $path;
        }

        $parts = explode('\\', $resourceClass);

        return $parts[count($parts) - 1];
    }

    private function operation(Request $request): string
    {
        $operationName = $request->attributes->getString('_api_operation_name', '');
        if ($operationName !== '') {
            return $operationName;
        }

        return strtolower($request->getMethod());
    }
}
