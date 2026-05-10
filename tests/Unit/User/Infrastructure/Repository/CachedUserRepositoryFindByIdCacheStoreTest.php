<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use Symfony\Contracts\Cache\ItemInterface;

final class CachedUserRepositoryFindByIdCacheStoreTest extends CachedUserRepositoryFindByIdTestCase
{
    public function testFindByIdCacheMissLoadsFromDatabaseAndCaches(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $user = $this->createUserMock($id);
        $item = $this->createMock(ItemInterface::class);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);
        $this->expectCacheMissLog($cacheKey, $id);
        $this->expectCacheStore($item, $cacheKey, $id);

        self::assertSame($user, $this->repository->findById($id));
    }

    private function expectCacheMissLog(string $cacheKey, string $id): void
    {
        $this->logger->expects($this->once())->method('info')->with(
            'Cache miss - loading user by ID from database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $id
                    && $context['operation'] === 'cache.miss'
            )
        );
    }

    private function expectCacheStore(ItemInterface $item, string $cacheKey, string $id): void
    {
        $item->expects($this->once())->method('expiresAfter')->with(600);
        $item->expects($this->once())->method('tag')->with(['user', "user.{$id}"]);
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey, $item) {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertSame(1.0, $beta);

                return $callback($item);
            }
        );
    }
}
