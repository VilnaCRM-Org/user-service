<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Provider\Http;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use App\Shared\Application\Provider\Http\AllowedMethodsProvider;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class AllowedMethodsProviderTest extends UnitTestCase
{
    public function testGetAllowedMethodsReturnsMethodsFromApiPlatform(): void
    {
        $operation = new Post(uriTemplate: 'users/batch');
        $operations = new Operations([$operation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsReturnsEmptyArrayForNonExistentPath(): void
    {
        $operation = new Post(uriTemplate: 'users/batch');
        $operations = new Operations([$operation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('/api/users/nonexistent');

        $this->assertEmpty($result);
    }

    public function testGetAllowedMethodsNormalizesPathCorrectly(): void
    {
        $operation = new Patch(uriTemplate: 'users/confirm');
        $operations = new Operations([$operation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('/api/users/confirm');

        $this->assertContains('PATCH', $result);
    }

    public function testGetAllowedMethodsHandlesOperationWithoutUriTemplate(): void
    {
        $operation = new Post();
        $operations = new Operations([$operation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertEmpty($result);
    }

    public function testGetAllowedMethodsHandlesPathWithLeadingSlash(): void
    {
        $operation = new Post(uriTemplate: '/api/users/batch');
        $operations = new Operations([$operation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsHandlesPathWithoutApiPrefix(): void
    {
        $operation = new Post(uriTemplate: 'users/batch');
        $operations = new Operations([$operation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('users/batch');

        $this->assertContains('POST', $result);
    }

    public function testGetAllowedMethodsIgnoresNonHttpOperations(): void
    {
        $httpOperation = new Post(uriTemplate: 'users/batch');
        $graphQlOperation = new Query();
        $operations = new Operations([$httpOperation, $graphQlOperation]);

        $apiResource = (new ApiResource())
            ->withOperations($operations);

        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        $provider = new AllowedMethodsProvider($factory);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertCount(1, $result);
        $this->assertContains('POST', $result);
    }
}
