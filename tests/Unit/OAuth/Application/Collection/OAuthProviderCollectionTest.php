<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Collection;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderCollectionTest extends UnitTestCase
{
    public function testGetReturnsProviderByKey(): void
    {
        $provider = $this->createProviderMock('github');
        $collection = new OAuthProviderCollection($provider);

        $this->assertSame($provider, $collection->get('github'));
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $collection = new OAuthProviderCollection();

        $this->assertNull($collection->get('unknown'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $provider = $this->createProviderMock('github');
        $collection = new OAuthProviderCollection($provider);

        $this->assertTrue($collection->has('github'));
    }

    public function testHasReturnsFalseForUnknownKey(): void
    {
        $collection = new OAuthProviderCollection();

        $this->assertFalse($collection->has('unknown'));
    }

    public function testKeysReturnsAllProviderNames(): void
    {
        $github = $this->createProviderMock('github');
        $google = $this->createProviderMock('google');
        $collection = new OAuthProviderCollection($github, $google);

        $keys = $collection->keys();

        $this->assertCount(2, $keys);
        $this->assertContains('github', $keys);
        $this->assertContains('google', $keys);
    }

    public function testCountReturnsNumberOfProviders(): void
    {
        $github = $this->createProviderMock('github');
        $google = $this->createProviderMock('google');
        $collection = new OAuthProviderCollection($github, $google);

        $this->assertCount(2, $collection);
    }

    public function testEmptyCollectionCountIsZero(): void
    {
        $collection = new OAuthProviderCollection();

        $this->assertCount(0, $collection);
    }

    public function testIsIterable(): void
    {
        $github = $this->createProviderMock('github');
        $collection = new OAuthProviderCollection($github);

        $items = iterator_to_array($collection);

        $this->assertCount(1, $items);
        $this->assertArrayHasKey('github', $items);
        $this->assertSame($github, $items['github']);
    }

    public function testThrowsOnDuplicateKey(): void
    {
        $first = $this->createProviderMock('github');
        $second = $this->createProviderMock('github');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Duplicate OAuth provider registration: github');

        new OAuthProviderCollection($first, $second);
    }

    private function createProviderMock(
        string $name,
    ): OAuthProviderInterface {
        $mock = $this->createMock(OAuthProviderInterface::class);
        $mock->method('getProvider')
            ->willReturn(OAuthProvider::fromString($name));

        return $mock;
    }
}
