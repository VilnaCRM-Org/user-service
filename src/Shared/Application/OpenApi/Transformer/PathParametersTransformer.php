<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class PathParametersTransformer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    private readonly PathParameterTransformer $parameterTransformer;

    public function __construct(
        PathParameterTransformer $parameterTransformer = new PathParameterTransformer()
    ) {
        $this->parameterTransformer = $parameterTransformer;
    }

    public function transform(OpenApi $openApi): OpenApi
    {
        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $openApi->getPaths()->addPath(
                $path,
                $this->transformPathItem($pathItem)
            );
        }

        return $openApi;
    }

    private function transformPathItem(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $getter = 'get' . $operation;
            $with = 'with' . $operation;

            $pathItem = $pathItem->$with(
                $this->transformOperation($pathItem->$getter())
            );
        }

        return $pathItem;
    }

    private function transformOperation(?Operation $operation): ?Operation
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
                fn (mixed $parameter) => $this->parameterTransformer->transform($parameter),
                $parameters
            )
        );
    }
}
