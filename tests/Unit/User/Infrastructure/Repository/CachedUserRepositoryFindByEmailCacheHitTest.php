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
        $this->expectDetachedCachedUserReload($cachedUser, $managedUser);
        $this->innerRepository->expects($this->never())->method('findByEmail');

        self::assertSame($managedUser, $this->repository->findByEmail($email));
    }

    public function testFindByEmailDeletesStaleEmailCacheKeyWhenReloadedEmailDiffers(): void
    {
        $oldEmail = $this->faker->email();
        $newEmail = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $cachedUser = $this->createUserMock($this->faker->uuid(), $oldEmail);
        $managedUser = $this->createUserMock($cachedUser->getId(), $newEmail);

        $this->expectBuildUserEmailKey($oldEmail, $cacheKey);
        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectDetachedCachedUserReload($cachedUser, $managedUser);
        $this->expectCacheDelete($cacheKey);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($oldEmail)
            ->willReturn(null);

        self::assertNull($this->repository->findByEmail($oldEmail));
    }
}
