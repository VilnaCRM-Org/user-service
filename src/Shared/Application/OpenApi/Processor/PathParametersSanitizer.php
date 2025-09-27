<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class PathParametersSanitizer
{
    private PathParameterCleaner $parameterCleaner;

    public function __construct(
        ?PathParameterCleaner $parameterCleaner = null
    ) {
        $this->parameterCleaner = $parameterCleaner
            ?? new PathParameterCleaner();
    }

    public function sanitize(OpenApi $openApi): OpenApi
    {
        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $openApi->getPaths()->addPath(
                $path,
                $this->sanitizePathItem($pathItem)
            );
        }

        return $openApi;
    }

    private function sanitizePathItem(PathItem $pathItem): PathItem
    {
        return $pathItem
            ->withGet($this->sanitizeOperation($pathItem->getGet()))
            ->withPost($this->sanitizeOperation($pathItem->getPost()))
            ->withPut($this->sanitizeOperation($pathItem->getPut()))
            ->withPatch($this->sanitizeOperation($pathItem->getPatch()))
            ->withDelete($this->sanitizeOperation($pathItem->getDelete()));
    }

    private function sanitizeOperation(?Operation $operation): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        if (!\is_array($operation->getParameters())) {
            return $operation;
        }

        $parameters = array_map(
            fn (mixed $parameter) => $this->parameterCleaner->clean($parameter),
            $operation->getParameters()
        );

        return $operation->withParameters($parameters);
    }
}
