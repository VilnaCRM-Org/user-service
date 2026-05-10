<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

abstract class CachedUserRepositoryFindByIdTestCase extends CachedUserRepositoryTestCase
{
    protected function expectBuildUserKey(string $id): string
    {
        $cacheKey = 'user.' . $id;
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        return $cacheKey;
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
                self::assertSame(1.0, $beta);

                return $value;
            }
        );
    }
}
