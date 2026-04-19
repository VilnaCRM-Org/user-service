<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByIdMergeFallbackTest extends CachedUserRepositoryFindByIdTestCase
{
    public function testFindByIdFallsBackToDatabaseWhenMergeFails(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);
        $freshUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager->expects($this->once())->method('merge')->with($cachedUser)
            ->willThrowException(new \RuntimeException('Merge failed'));
        $this->expectMergeWarning($cacheKey, $id);
        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($freshUser);

        self::assertSame($freshUser, $this->repository->findById($id));
    }

    private function expectMergeWarning(string $cacheKey, string $id): void
    {
        $this->logger->expects($this->once())->method('warning')->with(
            'Failed to reattach cached user - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $id
                    && $context['error'] === 'Merge failed'
                    && $context['operation'] === 'cache.merge.error'
            )
        );
    }
}
