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
        $firstProviderName = $this->createProviderName();
        $secondProviderName = $this->createProviderName();
        $collection = new OAuthProviderNameCollection([
            OAuthProvider::fromString($firstProviderName),
            OAuthProvider::fromString($secondProviderName),
        ]);

        self::assertSame([$firstProviderName, $secondProviderName], $collection->names());
        self::assertCount(2, $collection);
    }

    public function testCollectionSupportsTraversableProviders(): void
    {
        $firstProviderName = $this->createProviderName();
        $secondProviderName = $this->createProviderName();
        $collection = new OAuthProviderNameCollection(
            $this->providers($firstProviderName, $secondProviderName),
        );

        self::assertSame(
            [$firstProviderName, $secondProviderName],
            array_map(
                static fn (OAuthProvider $provider): string => (string) $provider,
                iterator_to_array($collection),
            ),
        );
    }

    public function testEmptyCollectionReturnsNoNames(): void
    {
        $collection = new OAuthProviderNameCollection([]);

        self::assertSame([], $collection->names());
        self::assertCount(0, $collection);
        self::assertSame([], iterator_to_array($collection));
    }

    /**
     * @return \Generator<int, OAuthProvider>
     */
    private function providers(string $firstProviderName, string $secondProviderName): \Generator
    {
        yield OAuthProvider::fromString($firstProviderName);
        yield OAuthProvider::fromString($secondProviderName);
    }

    private function createProviderName(): string
    {
        return strtolower($this->faker->unique()->lexify('provider????'));
    }
}
