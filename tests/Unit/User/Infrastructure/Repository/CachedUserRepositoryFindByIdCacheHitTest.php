<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByIdCacheHitTest extends CachedUserRepositoryFindByIdTestCase
{
    public function testFindReturnsCachedUserWhenManaged(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(true);
        $this->innerRepository->expects($this->never())->method('findById');

        self::assertSame($cachedUser, $this->repository->find($id));
    }

    public function testFindByIdReloadsDetachedCachedUserByIdentifier(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $cachedUser = $this->createUserMock($id);
        $managedUser = $this->createUserMock($id);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);
        $this->documentManager
            ->expects($this->once())
            ->method('find')
            ->with($cachedUser::class, $id)
            ->willReturn($managedUser);
        $this->innerRepository->expects($this->never())->method('findById');

        self::assertSame($managedUser, $this->repository->findById($id));
    }
}
