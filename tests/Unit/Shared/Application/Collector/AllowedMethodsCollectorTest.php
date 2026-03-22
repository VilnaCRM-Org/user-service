<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Collector;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use App\Shared\Application\Collector\AllowedMethodsCollector;
use App\Shared\Application\Matcher\AllowedMethodsOperationMatcher;
use App\Shared\Application\Normalizer\AllowedMethodsPathNormalizer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class AllowedMethodsCollectorTest extends UnitTestCase
{
    public function testCollectMergesMethodsAcrossResources(): void
    {
        $resourceOne = $this->resourceWithOperations([
            new Post(uriTemplate: 'users'),
        ]);

        $resourceTwo = $this->resourceWithOperations([
            new Get(uriTemplate: 'users'),
        ]);

        $collection = new ResourceMetadataCollection(
            User::class,
            [$resourceOne, $resourceTwo]
        );

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->with(User::class)->willReturn($collection);

        $pathNormalizer = new AllowedMethodsPathNormalizer();
        $matcher = new AllowedMethodsOperationMatcher($pathNormalizer);
        $collector = new AllowedMethodsCollector($factory, $matcher);

        $normalizedPath = $pathNormalizer->normalize('/api/users');
        $methods = $collector->collect(User::class, $normalizedPath);

        $this->assertSame(['POST', 'GET'], $methods);
    }

    /**
     * @param array<\ApiPlatform\Metadata\Operation> $operations
     */
    private function resourceWithOperations(array $operations): ApiResource
    {
        $operationsObj = new Operations($operations);

        return (new ApiResource())->withOperations($operationsObj);
    }
}
