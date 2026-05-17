<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use Symfony\Contracts\Cache\ItemInterface;

final class CachedUserRepositoryFindByEmailCacheStoreTest extends
    CachedUserRepositoryFindByEmailTestCase
{
    public function testFindByEmailCacheMissLoadsFromDatabaseAndCaches(): void
    {
        $email = $this->faker->email();
        $hash = $this->faker->sha256();
        $cacheKey = 'user.email.' . $hash;
        $user = $this->createUserMock($this->faker->uuid(), $email);
        $item = $this->createMock(ItemInterface::class);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheMissDependencies($email, $hash, $user);
        $this->expectCacheMissLog($cacheKey);
        $this->expectCacheStore($item, $cacheKey, $hash);

        self::assertSame($user, $this->repository->findByEmail($email));
    }

    public function testFindByEmailCacheMissSkipsNegativeCacheWrite(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $item = $this->createMock(ItemInterface::class);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->documentManager->expects($this->never())->method('contains');
        $this->expectCacheMissLog($cacheKey);
        $this->expectSkippedNegativeCacheStore($item, $cacheKey);

        self::assertNull($this->repository->findByEmail($email));
    }

    public function testFindByEmailFallsBackToDatabaseWhenCacheContainsStaleNegativeLookup(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheGet($cacheKey, null);
        $this->expectCacheDelete($cacheKey);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $this->documentManager->expects($this->never())->method('contains');

        self::assertSame($user, $this->repository->findByEmail($email));
    }

    private function expectCacheMissDependencies(string $email, string $hash, object $user): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($hash);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);
    }

    private function expectCacheMissLog(string $cacheKey): void
    {
        $this->logger->expects($this->once())->method('info')->with(
            'Cache miss - loading user by email',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['operation'] === 'cache.miss'
            )
        );
    }

    private function expectCacheStore(ItemInterface $item, string $cacheKey, string $hash): void
    {
        $item->expects($this->once())->method('expiresAfter')->with(300);
        $item->expects($this->once())
            ->method('tag')
            ->with(['user', "user.email.{$hash}"]);
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey, $item) {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertNull($beta);

                $save = true;
                $result = $callback($item, $save);

                self::assertTrue($save);

                return $result;
            }
        );
    }

    private function expectSkippedNegativeCacheStore(ItemInterface $item, string $cacheKey): void
    {
        $item->expects($this->never())->method('expiresAfter');
        $item->expects($this->never())->method('tag');
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey, $item) {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertNull($beta);

                $save = true;
                $result = $callback($item, $save);

                self::assertFalse($save);

                return $result;
            }
        );
    }
}
