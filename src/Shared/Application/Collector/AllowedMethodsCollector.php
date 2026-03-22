<?php

declare(strict_types=1);

namespace App\Shared\Application\Collector;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use App\Shared\Application\Matcher\AllowedMethodsOperationMatcher;

final readonly class AllowedMethodsCollector
{
    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private AllowedMethodsOperationMatcher $matcher
    ) {
    }

    /**
     * @param class-string $resourceClass
     *
     * @return array<int, string>
     */
    public function collect(string $resourceClass, string $normalizedPath): array
    {
        $methods = [];
        $metadata = $this->resourceMetadataCollectionFactory->create($resourceClass);

        foreach ($metadata as $resourceMetadata) {
            $methods = array_merge(
                $methods,
                $this->collectMethodsFromResource($resourceMetadata, $normalizedPath)
            );
        }

        return $methods;
    }

    /**
     * @return array<int, string>
     */
    private function collectMethodsFromResource(
        ApiResource $resourceMetadata,
        string $normalizedPath
    ): array {
        $methods = [];

        foreach ($resourceMetadata->getOperations() as $operation) {
            $method = $this->matcher->match($operation, $normalizedPath);

            if ($method !== null) {
                $methods[] = $method;
            }
        }

        return $methods;
    }
}
