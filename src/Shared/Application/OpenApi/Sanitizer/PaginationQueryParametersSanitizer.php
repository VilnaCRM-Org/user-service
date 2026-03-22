<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Sanitizer;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

use function array_keys;

final class PaginationQueryParametersSanitizer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private readonly PaginationOperationSanitizer $operationSanitizer
    ) {
    }

    public function sanitize(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);
            $paths->addPath($path, $this->sanitizePathItem($pathItem));
        }

        return $openApi;
    }

    private function sanitizePathItem(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $pathItem->{'with' . $operation}(
                $this->operationSanitizer->sanitize(
                    $pathItem->{'get' . $operation}()
                )
            );
        }

        return $pathItem;
    }
}
