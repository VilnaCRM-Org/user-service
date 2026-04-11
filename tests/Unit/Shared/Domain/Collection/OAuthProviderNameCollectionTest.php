<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Collection;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Domain\Collection\OAuthProviderNameCollection;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderNameCollectionTest extends UnitTestCase
{
    public function testNamesReturnsProviderNamesInOrder(): void
    {
        $collection = new OAuthProviderNameCollection([
            OAuthProvider::fromString('github'),
            OAuthProvider::fromString('google'),
        ]);

        self::assertSame(['github', 'google'], $collection->names());
        self::assertCount(2, $collection);
    }

    public function testCollectionSupportsTraversableProviders(): void
    {
        $collection = new OAuthProviderNameCollection($this->providers());

        self::assertSame(
            ['facebook', 'twitter'],
            array_map(
                static fn (OAuthProvider $provider): string => (string) $provider,
                iterator_to_array($collection),
            ),
        );
    }

    /**
     * @return \Generator<int, OAuthProvider>
     */
    private function providers(): \Generator
    {
        yield OAuthProvider::fromString('facebook');
        yield OAuthProvider::fromString('twitter');
    }
}
