<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByEmailCacheHitTest extends
    CachedUserRepositoryFindByEmailTestCase
{
    public function testFindByEmailReturnsCachedUserWhenManaged(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $cachedUser = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(true);
        $this->innerRepository->expects($this->never())->method('findByEmail');

        self::assertSame($cachedUser, $this->repository->findByEmail($email));
    }

    public function testFindByEmailReloadsDetachedCachedUserByIdentifier(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $cachedUser = $this->createUserMock($this->faker->uuid(), $email);
        $managedUser = $this->createUserMock($cachedUser->getId(), $email);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager
            ->expects($this->once())
            ->method('find')
            ->with($cachedUser::class, $cachedUser->getId())
            ->willReturn($managedUser);
        $this->innerRepository->expects($this->never())->method('findByEmail');

        self::assertSame($managedUser, $this->repository->findByEmail($email));
    }
}
