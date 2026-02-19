<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Sanitizer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

final class PaginationOperationSanitizer
{
    public function __construct(
        private readonly PaginationParameterSanitizer $parameterSanitizer
    ) {
    }

    public function sanitize(?Operation $operation): ?Operation
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
                    ? $this->parameterSanitizer->sanitize($parameter)
                    : $parameter,
                $operation->getParameters()
            )
        );
    }
}
