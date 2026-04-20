<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByEmailCacheMissTest extends CachedUserRepositoryFindByEmailTestCase
{
    public function testFindByEmailReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheGet($cacheKey, false);
        $this->cache->expectDelete(
            static function (string $actualCacheKey) use ($cacheKey): bool {
                self::assertSame($cacheKey, $actualCacheKey);

                return true;
            }
        );
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->documentManager->expects($this->never())->method('contains');

        self::assertNull($this->repository->findByEmail($email));
    }
}
