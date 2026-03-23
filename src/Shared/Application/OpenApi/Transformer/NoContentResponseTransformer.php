<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\OpenApi;

use function array_keys;

final class NoContentResponseTransformer
{
    public function __construct(
        private readonly PathItemNoContentTransformer $pathItemTransformer
    ) {
    }

    public function transform(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);
            $paths->addPath($path, $this->pathItemTransformer->transform($pathItem));
        }

        return $openApi;
    }
}
