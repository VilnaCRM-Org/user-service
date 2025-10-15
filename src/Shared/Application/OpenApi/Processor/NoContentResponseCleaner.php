<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;

use function array_keys;

final class NoContentResponseCleaner
{
    public function __construct(
        private readonly PathItemNoContentCleaner $pathItemCleaner
    ) {
    }

    public function clean(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);
            $paths->addPath($path, $this->pathItemCleaner->clean($pathItem));
        }

        return $openApi;
    }
}
