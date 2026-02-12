<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Provider;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use App\Internal\HealthCheck\Domain\ValueObject\HealthCheck;
use App\Shared\Application\Collector\AllowedMethodsCollector;
use App\Shared\Application\Matcher\AllowedMethodsOperationMatcher;
use App\Shared\Application\Normalizer\AllowedMethodsPathNormalizer;
use App\Shared\Application\Provider\AllowedMethodsProvider;
use App\Shared\Domain\ValueObject\ResourceClassAllowlist;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class AllowedMethodsProviderTest extends UnitTestCase
{
    public function testGetAllowedMethodsReturnsMethodsFromApiPlatform(): void
    {
        $provider = $this->createProviderWithOperations([
            new Post(uriTemplate: 'users/batch'),
        ]);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsReturnsEmptyArrayForNonExistentPath(): void
    {
        $provider = $this->createProviderWithOperations([
            new Post(uriTemplate: 'users/batch'),
        ]);

        $result = $provider->getAllowedMethods('/api/users/nonexistent');

        $this->assertEmpty($result);
    }

    public function testGetAllowedMethodsNormalizesPathCorrectly(): void
    {
        $provider = $this->createProviderWithOperations([
            new Patch(uriTemplate: 'users/confirm'),
        ]);

        $result = $provider->getAllowedMethods('/api/users/confirm');

        $this->assertContains('PATCH', $result);
    }

    public function testGetAllowedMethodsHandlesOperationWithoutUriTemplate(): void
    {
        $provider = $this->createProviderWithOperations([new Post()]);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertEmpty($result);
    }

    public function testGetAllowedMethodsHandlesPathWithLeadingSlash(): void
    {
        $provider = $this->createProviderWithOperations([
            new Post(uriTemplate: '/api/users/batch'),
        ]);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsHandlesPathWithoutApiPrefix(): void
    {
        $provider = $this->createProviderWithOperations([
            new Post(uriTemplate: 'users/batch'),
        ]);

        $result = $provider->getAllowedMethods('users/batch');

        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsIgnoresNonHttpOperations(): void
    {
        $provider = $this->createProviderWithOperations([
            new Post(uriTemplate: 'users/batch'),
            new Query(),
        ]);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertCount(1, $result);
        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsReturnsMultipleMethods(): void
    {
        $provider = $this->createProviderWithOperations([
            new Post(uriTemplate: 'users'),
            new Get(uriTemplate: 'users'),
        ]);

        $result = $provider->getAllowedMethods('/api/users');

        $this->assertCount(2, $result);
        $this->assertContains('POST', $result);
        $this->assertContains('GET', $result);
    }

    public function testGetAllowedMethodsConvertsToUpperCase(): void
    {
        $provider = $this->createProviderWithOperations([
            new HttpOperation(method: 'post', uriTemplate: 'users/batch'),
        ]);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertSame('POST', $result[0]);
        $this->assertNotSame('post', $result[0]);
    }

    public function testGetAllowedMethodsMergesMethodsAcrossResources(): void
    {
        $provider = $this->createProviderWithMultipleResources();

        $result = $provider->getAllowedMethods('/api/users');

        $this->assertSame(['POST', 'GET', 'PATCH'], $result);
        $this->assertSame([0, 1, 2], array_keys($result));
    }

    /**
     * @param array<\ApiPlatform\Metadata\Operation> $operations
     */
    private function createProviderWithOperations(array $operations): AllowedMethodsProvider
    {
        $apiResource = $this->resourceWithOperations($operations);
        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturnCallback(
            static fn (string $resourceClass) => $resourceClass === User::class
                ? $collection
                : new ResourceMetadataCollection($resourceClass, [])
        );

        $pathNormalizer = new AllowedMethodsPathNormalizer();
        $matcher = new AllowedMethodsOperationMatcher($pathNormalizer);
        $collector = new AllowedMethodsCollector($factory, $matcher);
        $resourceClassAllowlist = new ResourceClassAllowlist();

        return new AllowedMethodsProvider(
            $collector,
            $resourceClassAllowlist,
            $pathNormalizer
        );
    }

    /**
     * @param array<\ApiPlatform\Metadata\Operation> $operations
     */
    private function resourceWithOperations(array $operations): ApiResource
    {
        $operationsObj = new Operations($operations);

        return (new ApiResource())->withOperations($operationsObj);
    }

    private function createProviderWithMultipleResources(): AllowedMethodsProvider
    {
        $userResource = $this->resourceWithOperations([
            new Post(uriTemplate: 'users'),
            new Get(uriTemplate: 'users'),
        ]);

        $healthResource = $this->resourceWithOperations([
            new Post(uriTemplate: 'users'),
            new Patch(uriTemplate: 'users'),
        ]);

        $factory = $this->createMultiResourceFactory($userResource, $healthResource);
        $pathNormalizer = new AllowedMethodsPathNormalizer();
        $matcher = new AllowedMethodsOperationMatcher($pathNormalizer);
        $collector = new AllowedMethodsCollector($factory, $matcher);
        $resourceClassAllowlist = new ResourceClassAllowlist();

        return new AllowedMethodsProvider(
            $collector,
            $resourceClassAllowlist,
            $pathNormalizer
        );
    }

    private function createMultiResourceFactory(
        ApiResource $userResource,
        ApiResource $healthResource
    ): \PHPUnit\Framework\MockObject\MockObject&ResourceMetadataCollectionFactoryInterface {
        $factory = $this->createMock(
            ResourceMetadataCollectionFactoryInterface::class
        );
        $factory->method('create')->willReturnCallback(
            static fn (string $resourceClass) => match ($resourceClass) {
                User::class => new ResourceMetadataCollection(
                    User::class,
                    [$userResource]
                ),
                HealthCheck::class => new ResourceMetadataCollection(
                    HealthCheck::class,
                    [$healthResource]
                ),
                default => new ResourceMetadataCollection($resourceClass, []),
            }
        );

        return $factory;
    }
}
