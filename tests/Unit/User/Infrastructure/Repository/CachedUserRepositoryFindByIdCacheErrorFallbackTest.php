<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByIdCacheErrorFallbackTest extends CachedUserRepositoryFindByIdTestCase
{
    public function testFindByIdFallsBackToDatabaseOnCacheError(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = $this->expectBuildUserKey($id);
        $user = $this->createUserMock($id);

        $this->expectCacheFailure($cacheKey);
        $this->expectCacheErrorLog($cacheKey);
        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);
        $this->documentManager->expects($this->never())->method('contains');

        self::assertSame($user, $this->repository->findById($id));
    }

    private function expectCacheFailure(string $cacheKey): void
    {
        $this->cache->expectGet(
            static function (
                string $actualCacheKey,
                callable $callback,
                ?float $beta
            ) use ($cacheKey): never {
                self::assertSame($cacheKey, $actualCacheKey);
                self::assertIsCallable($callback);
                self::assertSame(1.0, $beta);

                throw new \RuntimeException('Cache unavailable');
            }
        );
    }

    private function expectCacheErrorLog(string $cacheKey): void
    {
        $this->logger->expects($this->once())->method('error')->with(
            'Cache error - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['error'] === 'Cache unavailable'
                    && $context['operation'] === 'cache.error'
            )
        );
    }
}
