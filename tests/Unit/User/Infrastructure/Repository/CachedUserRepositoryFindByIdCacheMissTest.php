<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByIdCacheMissTest extends CachedUserRepositoryFindByIdTestCase
{
    public function testFindByIdReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);

        $this->expectCacheGet($cacheKey, false);
        $this->cache->expectDelete(
            static function (string $actualCacheKey) use ($cacheKey): bool {
                self::assertSame($cacheKey, $actualCacheKey);

                return true;
            }
        );
        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);
        $this->documentManager->expects($this->never())->method('contains');

        self::assertNull($this->repository->findById($id));
    }
}
