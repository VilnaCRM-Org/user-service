<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Factory;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Application\Factory\OAuthProviderNameCollectionFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderNameCollectionFactoryTest extends UnitTestCase
{
    public function testEnabledFactoryPreservesProviderNames(): void
    {
        $providers = [OAuthProvider::fromString('github'), OAuthProvider::fromString('google')];
        $collection = (new OAuthProviderNameCollectionFactory(true))->create($providers);

        self::assertSame(['github', 'google'], $collection->names());
    }

    public function testDisabledFactoryDoesNotIterateProviderNames(): void
    {
        $providers = (static function (): \Generator {
            yield throw new \LogicException('Disabled provider names must not be resolved');
        })();
        $collection = (new OAuthProviderNameCollectionFactory(false))->create($providers);

        self::assertSame([], $collection->names());
    }

    public function testExplicitOptInRetainsProviderNames(): void
    {
        $collection = (new OAuthProviderNameCollectionFactory(true))->create([
            OAuthProvider::fromString('github'),
        ]);

        self::assertSame(['github'], $collection->names());
    }
}
