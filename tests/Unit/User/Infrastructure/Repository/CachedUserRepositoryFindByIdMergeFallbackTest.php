<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByIdMergeFallbackTest extends
    CachedUserRepositoryFindByIdTestCase
{
    public function testFindByIdFallsBackToDatabaseWhenReloadFails(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);
        $freshUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectReloadFailure($cachedUser, $id);
        $this->expectReloadWarning($cacheKey, $id);
        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($freshUser);

        self::assertSame($freshUser, $this->repository->findById($id));
    }

    public function testFindByIdFallsBackToDatabaseWhenReloadMisses(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);
        $freshUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectReloadMiss($cachedUser, $id);
        $this->expectReloadMissWarning($cacheKey, $id);
        $this->expectFindByIdFallback($id, $freshUser);

        self::assertSame($freshUser, $this->repository->findById($id));
    }

    public function testFindByIdFallsBackToDatabaseWhenReloadReturnsUnexpectedValue(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);
        $freshUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectInvalidReloadValue($cachedUser, $id);
        $this->expectInvalidReloadWarning($cacheKey, $id);
        $this->expectFindByIdFallback($id, $freshUser);

        self::assertSame($freshUser, $this->repository->findById($id));
    }

    private function expectReloadFailure(object $cachedUser, string $id): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager
            ->expects($this->once())
            ->method('find')
            ->with($cachedUser::class, $id)
            ->willThrowException(new \RuntimeException('Reload failed'));
    }

    private function expectReloadWarning(string $cacheKey, string $id): void
    {
        $this->logger->expects($this->once())->method('warning')->with(
            'Failed to reload detached cached user - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $id
                    && $context['error'] === 'Reload failed'
                    && $context['operation'] === 'cache.reload.error'
            )
        );
    }

    private function expectReloadMiss(object $cachedUser, string $id): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager
            ->expects($this->once())
            ->method('find')
            ->with($cachedUser::class, $id)
            ->willReturn(null);
    }

    private function expectInvalidReloadValue(object $cachedUser, string $id): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager
            ->expects($this->once())
            ->method('find')
            ->with($cachedUser::class, $id)
            ->willReturn(new \stdClass());
    }

    private function expectReloadMissWarning(string $cacheKey, string $id): void
    {
        $this->logger->expects($this->once())->method('warning')->with(
            'Detached cached user was not found - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $id
                    && $context['operation'] === 'cache.reload.miss'
            )
        );
    }

    private function expectInvalidReloadWarning(string $cacheKey, string $id): void
    {
        $this->logger->expects($this->once())->method('warning')->with(
            'Cache reload returned an unexpected value - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $id
                    && $context['operation'] === 'cache.reload.invalid'
            )
        );
    }

    private function expectFindByIdFallback(string $id, object $freshUser): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($freshUser);
    }
}
