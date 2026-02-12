<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Entity\UserInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CachedUserRepositoryFindByIdTest extends CachedUserRepositoryTestCase
{
    public function testFindReturnsCachedUserWhenManaged(): void
    {
        $id = (string) $this->faker->numberBetween(1, 9999);
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectDocumentManagerContains($cachedUser, true);

        $this->innerRepository
            ->expects($this->never())
            ->method('findById');

        $result = $this->repository->find($id);

        self::assertSame($cachedUser, $result);
    }

    public function testFindByIdReturnsFreshUserWhenCachedUserIsDetached(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);
        $freshUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectDocumentManagerContains($cachedUser, false);
        $this->expectInnerFindById($id, $freshUser);

        $result = $this->repository->findById($id);

        self::assertSame($freshUser, $result);
    }

    public function testFindByIdCacheMissLoadsFromDatabaseAndCaches(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $user = $this->createUserMock($id);

        $this->expectInnerFindById($id, $user);
        $this->expectDocumentManagerContains($user, true);
        $this->expectCacheMissLog($cacheKey, $id);
        $this->expectCacheMissStore($cacheKey, $id);

        $result = $this->repository->findById($id);

        self::assertSame($user, $result);
    }

    public function testFindByIdReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);

        $this->expectCacheGet($cacheKey, false);
        $this->expectCacheDelete($cacheKey);
        $this->expectInnerFindById($id, null);
        $this->expectDocumentManagerNeverContains();

        $result = $this->repository->findById($id);

        self::assertNull($result);
    }

    public function testFindByIdFallsBackToDatabaseOnCacheError(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $user = $this->createUserMock($id);

        $this->expectCacheGetThrows($cacheKey, 'Cache unavailable');
        $this->expectCacheErrorLog($cacheKey, 'Cache unavailable');
        $this->expectInnerFindById($id, $user);
        $this->expectDocumentManagerNeverContains();

        $result = $this->repository->findById($id);

        self::assertSame($user, $result);
    }

    private function expectBuildUserKey(string $id): string
    {
        $cacheKey = 'user.' . $id;
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        return $cacheKey;
    }

    private function expectCacheGet(
        string $cacheKey,
        array|bool|float|int|object|string|null $value
    ): void {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), 1.0)
            ->willReturn($value);
    }

    private function expectDocumentManagerContains(UserInterface $user, bool $contains): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn($contains);
    }

    private function expectDocumentManagerNeverContains(): void
    {
        $this->documentManager
            ->expects($this->never())
            ->method('contains');
    }

    private function expectInnerFindById(string $id, ?UserInterface $user): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);
    }

    private function expectCacheDelete(string $cacheKey): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey);
    }

    private function expectCacheGetThrows(string $cacheKey, string $message): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->anything(), $this->anything())
            ->willThrowException(new \RuntimeException($message));
    }

    private function expectCacheMissLog(string $cacheKey, string $id): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading user by ID from database',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['user_id'] === $id
                        && $context['operation'] === 'cache.miss'
                )
            );
    }

    private function expectCacheErrorLog(string $cacheKey, string $error): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['error'] === $error
                        && $context['operation'] === 'cache.error'
                )
            );
    }

    private function expectCacheMissStore(string $cacheKey, string $id): void
    {
        $item = $this->createCacheItemForUser($id);
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), 1.0)
            ->willReturnCallback(
                static fn (string $key, callable $callback) => $callback($item)
            );
    }

    private function createCacheItemForUser(string $id): \PHPUnit\Framework\MockObject\MockObject&ItemInterface
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(600);
        $item->expects($this->once())
            ->method('tag')
            ->with(['user', "user.{$id}"]);

        return $item;
    }
}
