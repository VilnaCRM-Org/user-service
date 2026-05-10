<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use App\Shared\Application\Matcher\AllowedMethodsOperationMatcher;
use App\Shared\Application\Normalizer\AllowedMethodsPathNormalizer;
use App\Shared\Application\Resolver\AllowedMethodsResolver;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class AllowedMethodsResolverTest extends UnitTestCase
{
    public function testCollectMergesMethodsAcrossMultipleApiResources(): void
    {
        $firstResource = (new ApiResource())->withOperations(
            new Operations([new Post(uriTemplate: 'users')])
        );
        $secondResource = (new ApiResource())->withOperations(
            new Operations([new Get(uriTemplate: 'users')])
        );

        $collection = new ResourceMetadataCollection(
            User::class,
            [$firstResource, $secondResource]
        );

        $factory = $this->createMock(
            ResourceMetadataCollectionFactoryInterface::class
        );
        $factory->method('create')->willReturn($collection);

        $pathNormalizer = new AllowedMethodsPathNormalizer();
        $matcher = new AllowedMethodsOperationMatcher($pathNormalizer);
        $resolver = new AllowedMethodsResolver($factory, $matcher);

        $result = $resolver->collect(User::class, 'users');

        $this->assertSame(['POST', 'GET'], $result);
    }
}
