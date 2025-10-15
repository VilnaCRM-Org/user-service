<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class PathParametersSanitizer
{
    private readonly PathParameterCleaner $parameterCleaner;

    public function __construct(
        PathParameterCleaner $parameterCleaner = new PathParameterCleaner()
    ) {
        $this->parameterCleaner = $parameterCleaner;
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
        return match (true) {
            $operation === null => null,
            !\is_array($operation->getParameters()) => $operation,
            default => $operation->withParameters(
                array_map(
                    fn (mixed $parameter) => $this->parameterCleaner->clean(
                        $parameter
                    ),
                    $operation->getParameters()
                )
            ),
        };
    }
}
