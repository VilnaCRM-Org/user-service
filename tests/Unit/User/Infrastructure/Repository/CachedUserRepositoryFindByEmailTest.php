<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Entity\UserInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CachedUserRepositoryFindByEmailTest extends CachedUserRepositoryTestCase
{
    public function testFindByEmailReturnsCachedUserWhenManaged(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $this->expectBuildUserEmailKey($email, $cacheKey);
        $cachedUser = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectCacheGet($cacheKey, $cachedUser);
        $this->expectDocumentManagerContains($cachedUser, true);

        $this->innerRepository
            ->expects($this->never())
            ->method('findById');

        $result = $this->repository->findByEmail($email);

        self::assertSame($cachedUser, $result);
    }

    public function testFindByEmailCacheMissLoadsFromDatabaseAndCaches(): void
    {
        $email = $this->faker->email();
        $hash = $this->faker->sha256();
        $cacheKey = 'user.email.' . $hash;
        $this->expectBuildUserEmailKey($email, $cacheKey);
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectHashEmail($email, $hash);
        $this->expectInnerFindByEmail($email, $user);
        $this->expectDocumentManagerContains($user, true);
        $this->expectCacheMissLog($cacheKey);
        $this->expectCacheMissStore($cacheKey, $hash);

        $result = $this->repository->findByEmail($email);

        self::assertSame($user, $result);
    }

    public function testFindByEmailReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $this->expectBuildUserEmailKey($email, $cacheKey);

        $this->expectCacheGet($cacheKey, false);
        $this->expectCacheDelete($cacheKey);
        $this->expectInnerFindByEmail($email, null);
        $this->expectDocumentManagerNeverContains();

        $result = $this->repository->findByEmail($email);

        self::assertNull($result);
    }

    public function testFindByEmailFallsBackToDatabaseOnCacheError(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $this->expectBuildUserEmailKey($email, $cacheKey);
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->expectCacheGetThrows($cacheKey, 'Cache unavailable');
        $this->expectCacheErrorLog($cacheKey, 'Cache unavailable');
        $this->expectInnerFindByEmail($email, $user);
        $this->expectDocumentManagerNeverContains();

        $result = $this->repository->findByEmail($email);

        self::assertSame($user, $result);
    }

    private function expectBuildUserEmailKey(string $email, string $cacheKey): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);
    }

    private function expectHashEmail(string $email, string $hash): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($hash);
    }

    private function expectCacheGet(
        string $cacheKey,
        array|bool|float|int|object|string|null $value
    ): void {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), null)
            ->willReturn($value);
    }

    private function expectDocumentManagerContains(UserInterface $user, bool $contains): void
    {
        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn($contains);
    }

    private function expectDocumentManagerNeverContains(): void
    {
        $this->documentManager
            ->expects($this->never())
            ->method('contains');
    }

    private function expectInnerFindByEmail(string $email, ?UserInterface $user): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
    }

    private function expectCacheDelete(string $cacheKey): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey);
    }

    private function expectCacheGetThrows(string $cacheKey, string $message): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->anything(), $this->anything())
            ->willThrowException(new \RuntimeException($message));
    }

    private function expectCacheMissLog(string $cacheKey): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading user by email',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['operation'] === 'cache.miss'
                )
            );
    }

    private function expectCacheErrorLog(string $cacheKey, string $error): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['error'] === $error
                        && $context['operation'] === 'cache.error'
                )
            );
    }

    private function expectCacheMissStore(string $cacheKey, string $hash): void
    {
        $item = $this->createCacheItemForEmail($hash);
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), null)
            ->willReturnCallback(
                static fn (string $key, callable $callback) => $callback($item)
            );
    }

    private function createCacheItemForEmail(string $hash): ItemInterface
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(300);
        $item->expects($this->once())
            ->method('tag')
            ->with(['user', 'user.email', "user.email.{$hash}"]);

        return $item;
    }
}
