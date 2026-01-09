<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider\Http;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

final readonly class AllowedMethodsProvider
{
    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory
    ) {
    }

    /**
     * Get allowed HTTP methods for a given URI path from API Platform metadata.
     *
     * @return array<int, string> List of allowed HTTP methods (e.g., ['POST', 'GET'])
     */
    public function getAllowedMethods(string $path): array
    {
        $normalizedPath = $this->normalizePath($path);
        $allowedMethods = [];

        foreach ($this->getAllResourceClasses() as $resourceClass) {
            $metadata = $this->resourceMetadataCollectionFactory->create($resourceClass);

            foreach ($metadata as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operation) {
                    if (!$operation instanceof HttpOperation) {
                        continue;
                    }

                    $operationPath = $this->getOperationPath($operation);
                    if ($operationPath === null) {
                        continue;
                    }

                    if ($this->normalizePath($operationPath) === $normalizedPath) {
                        $method = $operation->getMethod();
                        if ($method !== null) {
                            $allowedMethods[] = strtoupper($method);
                        }
                    }
                }
            }
        }

        return array_unique($allowedMethods);
    }

    /**
     * @return array<int, class-string>
     */
    private function getAllResourceClasses(): array
    {
        return [
            \App\User\Domain\Entity\User::class,
            \App\Internal\HealthCheck\Domain\ValueObject\HealthCheck::class,
        ];
    }

    private function getOperationPath(HttpOperation $operation): ?string
    {
        $uriTemplate = $operation->getUriTemplate();
        if ($uriTemplate === null) {
            return null;
        }

        // Remove leading slash and '/api/' prefix to match path format
        $path = ltrim($uriTemplate, '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }

        return $path;
    }

    private function normalizePath(string $path): string
    {
        // Remove leading '/api/' prefix and normalize
        $normalized = ltrim($path, '/');
        if (str_starts_with($normalized, 'api/')) {
            $normalized = substr($normalized, 4);
        }

        return $normalized;
    }
}
