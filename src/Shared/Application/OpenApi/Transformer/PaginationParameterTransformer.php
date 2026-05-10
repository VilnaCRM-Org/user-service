<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

use function in_array;

final class PaginationParameterTransformer
{
    private const NON_EMPTY_PAGINATION_PARAMETERS = ['page', 'itemsPerPage', 'partial'];
    private const INTEGER_PAGINATION_PARAMETERS = ['page', 'itemsPerPage'];

    public function transform(OpenApiParameter $parameter): mixed
    {
        if (!$this->isNonEmptyPaginationQueryParameter($parameter)) {
            return $parameter;
        }

        $parameter = $parameter->withAllowEmptyValue(false);

        if (!$this->isPositiveIntegerPaginationQueryParameter($parameter)) {
            return $parameter;
        }

        $schema = $parameter->getSchema();
        $schema['minimum'] = 1;

        return $parameter->withSchema($schema);
    }

    private function isNonEmptyPaginationQueryParameter(OpenApiParameter $parameter): bool
    {
        if ($parameter->getIn() !== 'query') {
            return false;
        }

        return in_array(
            $parameter->getName(),
            self::NON_EMPTY_PAGINATION_PARAMETERS,
            true
        );
    }

    private function isPositiveIntegerPaginationQueryParameter(OpenApiParameter $parameter): bool
    {
        return in_array(
            $parameter->getName(),
            self::INTEGER_PAGINATION_PARAMETERS,
            true
        );
    }
}
