<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

abstract class CachedUserRepositoryFindByEmailTestCase extends CachedUserRepositoryTestCase
{
    protected function expectBuildUserEmailKey(string $email, string $cacheKey): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);
    }

    protected function expectCacheGet(
        string $cacheKey,
        array|bool|float|int|object|string|null $value
    ): void {
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey, $value) {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertIsCallable($callback);
                self::assertNull($beta);

                return $value;
            }
        );
    }
}
