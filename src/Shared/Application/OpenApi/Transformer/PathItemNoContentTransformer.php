<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\PathItem;

final class PathItemNoContentTransformer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private readonly OperationNoContentTransformer $operationTransformer
    ) {
    }

    public function transform(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $this->applyTransform($pathItem, $operation);
        }

        return $pathItem;
    }

    private function applyTransform(
        PathItem $pathItem,
        string $operation
    ): PathItem {
        $getter = 'get' . $operation;
        $with = 'with' . $operation;

        return $pathItem->{$with}(
            $this->operationTransformer->transform(
                $pathItem->{$getter}()
            )
        );
    }
}
