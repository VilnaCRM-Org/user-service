<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByEmailMergeFallbackTest extends CachedUserRepositoryFindByEmailTestCase
{
    public function testFindByEmailFallsBackToDatabaseWhenReloadFails(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $cachedUser = $this->createUserMock($this->faker->uuid(), $email);
        $freshUser = $this->createUserMock($cachedUser->getId(), $email);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectReloadFailure($cachedUser);
        $this->expectReloadWarning($cacheKey, $cachedUser->getId());
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($freshUser);

        self::assertSame($freshUser, $this->repository->findByEmail($email));
    }

    private function expectReloadFailure(object $cachedUser): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager
            ->expects($this->once())
            ->method('find')
            ->with($cachedUser::class, $cachedUser->getId())
            ->willThrowException(new \RuntimeException('Reload failed'));
    }

    private function expectReloadWarning(string $cacheKey, string $userId): void
    {
        $this->logger->expects($this->once())->method('warning')->with(
            'Failed to reload detached cached user - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['user_id'] === $userId
                    && $context['error'] === 'Reload failed'
                    && $context['operation'] === 'cache.reload.error'
            )
        );
    }
}
