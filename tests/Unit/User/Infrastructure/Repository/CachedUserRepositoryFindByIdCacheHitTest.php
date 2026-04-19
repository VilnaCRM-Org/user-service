<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByIdCacheHitTest extends CachedUserRepositoryFindByIdTestCase
{
    public function testFindReturnsCachedUserWhenManaged(): void
    {
        $id = (string) $this->faker->numberBetween(1, 9999);
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

    public function testFindByIdReattachesDetachedCachedUserWithoutDatabaseQuery(): void
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
            ->method('merge')
            ->with($cachedUser)
            ->willReturn($managedUser);
        $this->innerRepository->expects($this->never())->method('findById');

        self::assertSame($managedUser, $this->repository->findById($id));
    }
}
