<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CachedUserRepositoryFindByEmailCacheErrorFallbackTest extends
    CachedUserRepositoryFindByEmailTestCase
{
    public function testFindByEmailFallsBackToDatabaseOnCacheError(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectBuildUserEmailKey($email, $cacheKey);
        $this->expectCacheFailure($cacheKey);
        $this->expectCacheErrorLog($cacheKey);
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $this->documentManager->expects($this->never())->method('contains');

        self::assertSame($user, $this->repository->findByEmail($email));
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
                self::assertNull($beta);

                throw CacheOperationFailedException::unavailable();
            }
        );
    }

    private function expectCacheErrorLog(string $cacheKey): void
    {
        $this->logger->expects($this->once())->method('error')->with(
            'Cache error - falling back to database',
            $this->callback(
                static fn (array $context): bool => $context['cache_key'] === $cacheKey
                    && $context['error'] === CacheOperationFailedException::UNAVAILABLE_MESSAGE
                    && $context['operation'] === 'cache.error'
            )
        );
    }
}
