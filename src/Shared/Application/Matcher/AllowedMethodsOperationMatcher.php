<?php

declare(strict_types=1);

namespace App\Shared\Application\Matcher;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Normalizer\AllowedMethodsPathNormalizer;

final readonly class AllowedMethodsOperationMatcher
{
    public function __construct(
        private AllowedMethodsPathNormalizer $pathNormalizer
    ) {
    }

    public function match(Operation $operation, string $normalizedPath): ?string
    {
        if (!$operation instanceof HttpOperation) {
            return null;
        }

        $uriTemplate = $operation->getUriTemplate();

        if ($uriTemplate === null) {
            return null;
        }

        if ($this->pathNormalizer->normalize($uriTemplate) !== $normalizedPath) {
            return null;
        }

        $method = $operation->getMethod();

        return $method === null ? null : strtoupper($method);
    }
}
