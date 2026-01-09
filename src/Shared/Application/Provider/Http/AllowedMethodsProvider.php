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
     * @return array<int, string>
     */
    public function getAllowedMethods(string $path): array
    {
        $normalizedPath = $this->normalizePath($path);
        $allowedMethods = $this->collectMethodsFromAllResources($normalizedPath);

        return array_unique($allowedMethods);
    }

    /**
     * @return array<int, string>
     */
    private function collectMethodsFromAllResources(string $normalizedPath): array
    {
        $methods = [];
        foreach ($this->getAllResourceClasses() as $resourceClass) {
            /** @infection-ignore-all */
            $methods = array_merge(
                $methods,
                $this->collectMethodsFromResource($resourceClass, $normalizedPath)
            );
        }

        return $methods;
    }

    /**
     * @param class-string $resourceClass
     *
     * @return array<int, string>
     */
    private function collectMethodsFromResource(
        string $resourceClass,
        string $normalizedPath
    ): array {
        $methods = [];
        $metadata = $this->resourceMetadataCollectionFactory->create($resourceClass);

        foreach ($metadata as $resourceMetadata) {
            /** @infection-ignore-all */
            $methods = array_merge(
                $methods,
                $this->extractMethodsFromOperations($resourceMetadata, $normalizedPath)
            );
        }

        return $methods;
    }

    /**
     * @return array<int, string>
     */
    private function extractMethodsFromOperations(
        \ApiPlatform\Metadata\ApiResource $resourceMetadata,
        string $normalizedPath
    ): array {
        $methods = [];
        foreach ($resourceMetadata->getOperations() as $operation) {
            $method = $this->extractMethodIfMatches($operation, $normalizedPath);
            if ($method !== null) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    private function extractMethodIfMatches(
        \ApiPlatform\Metadata\Operation $operation,
        string $normalizedPath
    ): ?string {
        if (!$operation instanceof HttpOperation) {
            return null;
        }

        $operationPath = $this->getOperationPath($operation);
        if ($operationPath === null) {
            return null;
        }

        if ($this->normalizePath($operationPath) !== $normalizedPath) {
            return null;
        }

        $method = $operation->getMethod();
        /** @infection-ignore-all */
        return $method !== null ? strtoupper($method) : null;
    }

    /**
     * @return array<int, class-string>
     */
    private function getAllResourceClasses(): array
    {
        /** @infection-ignore-all */
        return [
            \App\User\Domain\Entity\User::class,
            \App\Internal\HealthCheck\Domain\ValueObject\HealthCheck::class,
        ];
    }

    private function getOperationPath(HttpOperation $operation): ?string
    {
        $uriTemplate = $operation->getUriTemplate();

        return $uriTemplate !== null ? $this->normalizePath($uriTemplate) : null;
    }

    private function normalizePath(string $path): string
    {
        $normalized = ltrim($path, '/');

        return str_starts_with($normalized, 'api/')
            ? substr($normalized, 4)
            : $normalized;
    }
}
