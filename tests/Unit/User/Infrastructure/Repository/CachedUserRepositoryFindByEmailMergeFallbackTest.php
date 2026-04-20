<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByEmailMergeFallbackTest
    extends CachedUserRepositoryFindByEmailTestCase
{
    public function testFindByEmailFallsBackToDatabaseWhenMergeFails(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $cachedUser = $this->createUserMock($this->faker->uuid(), $email);
        $freshUser = $this->createUserMock($cachedUser->getId(), $email);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager->expects($this->once())->method('merge')->with($cachedUser)
            ->willThrowException(new \RuntimeException('Merge failed'));
        $this->expectMergeWarning($cacheKey, $cachedUser->getId());
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($freshUser);

        self::assertSame($freshUser, $this->repository->findByEmail($email));
    }

    private function expectMergeWarning(string $cacheKey, string $userId): void
    {
        $this->logger->expects($this->once())->method('warning')->with(
            'Failed to reattach cached user - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $userId
                    && $context['error'] === 'Merge failed'
                    && $context['operation'] === 'cache.merge.error'
            )
        );
    }
}
