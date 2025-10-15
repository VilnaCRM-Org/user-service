<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\PathItem;

final class PathItemNoContentCleaner
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private readonly OperationNoContentCleaner $operationCleaner
    ) {
    }

    public function clean(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $this->applyCleaner($pathItem, $operation);
        }

        return $pathItem;
    }

    private function applyCleaner(
        PathItem $pathItem,
        string $operation
    ): PathItem {
        $getter = 'get' . $operation;
        $with = 'with' . $operation;

        return $pathItem->{$with}(
            $this->operationCleaner->clean(
                $pathItem->{$getter}()
            )
        );
    }
}
