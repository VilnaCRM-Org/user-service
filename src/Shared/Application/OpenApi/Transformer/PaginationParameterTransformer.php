<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

use function in_array;

final class PaginationParameterTransformer
{
    private const PAGINATION_PARAMETERS = ['page', 'itemsPerPage'];

    public function transform(OpenApiParameter $parameter): mixed
    {
        if (!$this->isPaginationQueryParameter($parameter)) {
            return $parameter;
        }

        $schema = $parameter->getSchema();
        $schema['minimum'] = 1;

        return $parameter
            ->withAllowEmptyValue(false)
            ->withSchema($schema);
    }

    private function isPaginationQueryParameter(OpenApiParameter $parameter): bool
    {
        if ($parameter->getIn() !== 'query') {
            return false;
        }

        return in_array(
            $parameter->getName(),
            self::PAGINATION_PARAMETERS,
            true
        );
    }
}
