<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

final class PaginationOperationTransformer
{
    public function __construct(
        private readonly PaginationParameterTransformer $parameterTransformer
    ) {
    }

    public function transform(?Operation $operation): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        if (!\is_array($operation->getParameters())) {
            return $operation;
        }

        return $operation->withParameters(
            array_map(
                fn (mixed $parameter) => $parameter instanceof OpenApiParameter
                    ? $this->parameterTransformer->transform($parameter)
                    : $parameter,
                $operation->getParameters()
            )
        );
    }
}
