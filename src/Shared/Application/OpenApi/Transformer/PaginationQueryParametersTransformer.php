<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

use function array_keys;

final class PaginationQueryParametersTransformer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private readonly PaginationOperationTransformer $operationTransformer
    ) {
    }

    public function transform(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);
            $paths->addPath($path, $this->transformPathItem($pathItem));
        }

        return $openApi;
    }

    private function transformPathItem(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $pathItem->{'with' . $operation}(
                $this->operationTransformer->transform(
                    $pathItem->{'get' . $operation}()
                )
            );
        }

        return $pathItem;
    }
}
