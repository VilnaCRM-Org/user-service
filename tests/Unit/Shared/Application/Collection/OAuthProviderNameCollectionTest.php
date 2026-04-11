<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Collection;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Application\Collection\OAuthProviderNameCollection;
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
}
