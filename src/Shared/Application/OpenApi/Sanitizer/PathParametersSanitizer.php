<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Sanitizer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;

final class PathParametersSanitizer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

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
        foreach (self::OPERATIONS as $operation) {
            $getter = 'get' . $operation;
            $with = 'with' . $operation;

            $pathItem = $pathItem->$with(
                $this->sanitizeOperation($pathItem->$getter())
            );
        }

        return $pathItem;
    }

    private function sanitizeOperation(?Operation $operation): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        $parameters = $operation->getParameters();

        if (!\is_array($parameters)) {
            return $operation;
        }

        return $operation->withParameters(
            array_map(
                fn (mixed $parameter) => $this->parameterCleaner->clean($parameter),
                $parameters
            )
        );
    }
}
