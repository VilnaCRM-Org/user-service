<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider;

use App\Shared\Application\Collector\AllowedMethodsCollector;
use App\Shared\Application\Normalizer\AllowedMethodsPathNormalizer;
use App\Shared\Application\ValueObject\ResourceClassAllowlist;

final readonly class AllowedMethodsProvider
{
    public function __construct(
        private AllowedMethodsCollector $collector,
        private ResourceClassAllowlist $resourceClassAllowlist,
        private AllowedMethodsPathNormalizer $pathNormalizer
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedMethods(string $path): array
    {
        $normalizedPath = $this->pathNormalizer->normalize($path);
        $methods = [];

        foreach ($this->resourceClassAllowlist->all() as $resourceClass) {
            $methods = array_merge(
                $methods,
                $this->collector->collect($resourceClass, $normalizedPath)
            );
        }

        return array_values(array_unique($methods));
    }
}
