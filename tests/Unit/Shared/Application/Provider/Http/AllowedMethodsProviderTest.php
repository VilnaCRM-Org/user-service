<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Provider\Http;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
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
            new Post(uriTemplate: 'users/batch'),
        ]);

        $result = $provider->getAllowedMethods('/api/users/batch');

        $this->assertSame('POST', $result[0]);
        $this->assertNotSame('post', $result[0]);
    }

    /**
     * @param array<\ApiPlatform\Metadata\Operation> $operations
     */
    private function createProviderWithOperations(array $operations): AllowedMethodsProvider
    {
        $operationsObj = new Operations($operations);
        $apiResource = (new ApiResource())->withOperations($operationsObj);
        $collection = new ResourceMetadataCollection(User::class, [$apiResource]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        return new AllowedMethodsProvider($factory);
    }
}
