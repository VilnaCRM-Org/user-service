<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Sanitizer;

use ApiPlatform\OpenApi\Model\Operation;

final class PaginationOperationSanitizer
{
    public function __construct(
        private readonly PaginationParameterSanitizer $parameterSanitizer
    ) {
    }

    public function sanitize(?Operation $operation): ?Operation
    {
        return match (true) {
            $operation === null => null,
            !\is_array($operation->getParameters()) => $operation,
            default => $operation->withParameters(
                array_map(
                    fn (mixed $parameter) => $this->parameterSanitizer
                        ->sanitize($parameter),
                    $operation->getParameters()
                )
            ),
        };
    }
}
