<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\CachedUserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CachedUserRepositoryTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $innerRepository;
    private TagAwareCacheInterface&MockObject $cache;
    private CacheKeyBuilder&MockObject $cacheKeyBuilder;
    private LoggerInterface&MockObject $logger;
    private DocumentManager&MockObject $documentManager;
    private CachedUserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = $this->createMock(UserRepositoryInterface::class);
        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->documentManager = $this->createMock(DocumentManager::class);

        $this->repository = new CachedUserRepository(
            $this->innerRepository,
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger,
            $this->documentManager
        );
    }

    public function testFindReturnsCachedUserWhenManaged(): void
    {
        $id = (string) $this->faker->numberBetween(1, 9999);
        $cacheKey = 'user.' . $id;
        $cachedUser = $this->createUserMock($id);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), 1.0)
            ->willReturn($cachedUser);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(true);

        $this->innerRepository
            ->expects($this->never())
            ->method('findById');

        $result = $this->repository->find($id);

        self::assertSame($cachedUser, $result);
    }

    public function testFindByIdReturnsFreshUserWhenCachedUserIsDetached(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = 'user.' . $id;
        $cachedUser = $this->createUserMock($id);
        $freshUser = $this->createUserMock($id);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), 1.0)
            ->willReturn($cachedUser);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(false);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($freshUser);

        $result = $this->repository->findById($id);

        self::assertSame($freshUser, $result);
    }

    public function testFindByIdCacheMissLoadsFromDatabaseAndCaches(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = 'user.' . $id;
        $user = $this->createUserMock($id);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading user by ID from database',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['user_id'] === $id
                        && $context['operation'] === 'cache.miss'
                )
            );

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), 1.0)
            ->willReturnCallback(function (string $key, callable $callback) use ($id) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(600);
                $item->expects($this->once())
                    ->method('tag')
                    ->with(['user', "user.{$id}"]);

                return $callback($item);
            });

        $result = $this->repository->findById($id);

        self::assertSame($user, $result);
    }

    public function testFindByIdReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = 'user.' . $id;

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), 1.0)
            ->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $this->documentManager
            ->expects($this->never())
            ->method('contains');

        $result = $this->repository->findById($id);

        self::assertNull($result);
    }

    public function testFindByIdFallsBackToDatabaseOnCacheError(): void
    {
        $id = $this->faker->uuid();
        $cacheKey = 'user.' . $id;
        $user = $this->createUserMock($id);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($id)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->anything(), $this->anything())
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['error'] === 'Cache unavailable'
                        && $context['operation'] === 'cache.error'
                )
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);

        $this->documentManager
            ->expects($this->never())
            ->method('contains');

        $result = $this->repository->findById($id);

        self::assertSame($user, $result);
    }

    public function testFindByEmailReturnsCachedUserWhenManaged(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $cachedUser = $this->createUserMock($this->faker->uuid(), $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), null)
            ->willReturn($cachedUser);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($cachedUser)
            ->willReturn(true);

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
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($hash);

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);

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

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), null)
            ->willReturnCallback(function (string $key, callable $callback) use ($hash) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(300);
                $item->expects($this->once())
                    ->method('tag')
                    ->with(['user', 'user.email', "user.email.{$hash}"]);

                return $callback($item);
            });

        $result = $this->repository->findByEmail($email);

        self::assertSame($user, $result);
    }

    public function testFindByEmailReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->callback('is_callable'), null)
            ->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->documentManager
            ->expects($this->never())
            ->method('contains');

        $result = $this->repository->findByEmail($email);

        self::assertNull($result);
    }

    public function testFindByEmailFallsBackToDatabaseOnCacheError(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.' . $this->faker->sha256();
        $user = $this->createUserMock($this->faker->uuid(), $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->anything(), $this->anything())
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(
                    static fn (array $context): bool => $context['cache_key'] === $cacheKey
                        && $context['error'] === 'Cache unavailable'
                        && $context['operation'] === 'cache.error'
                )
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->documentManager
            ->expects($this->never())
            ->method('contains');

        $result = $this->repository->findByEmail($email);

        self::assertSame($user, $result);
    }

    public function testSaveDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->repository->save($user);
    }

    public function testDeleteDelegatesToInnerRepository(): void
    {
        $user = $this->createUserMock($this->faker->uuid(), $this->faker->email());

        $this->innerRepository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->repository->delete($user);
    }

    public function testSaveBatchDelegatesToInnerRepository(): void
    {
        $users = [
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
        ];

        $this->innerRepository
            ->expects($this->once())
            ->method('saveBatch')
            ->with($users);

        $this->repository->saveBatch($users);
    }

    public function testDeleteBatchDelegatesToInnerRepository(): void
    {
        $users = [
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
            $this->createUserMock($this->faker->uuid(), $this->faker->email()),
        ];

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteBatch')
            ->with($users);

        $this->repository->deleteBatch($users);
    }

    public function testDeleteAllInvalidatesCache(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['user', 'user.collection']);

        $this->repository->deleteAll();
    }

    public function testDeleteAllLogsWarningWhenInvalidationFails(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['user', 'user.collection'])
            ->willThrowException(new \RuntimeException('Cache error'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to invalidate cache after deleteAll',
                $this->callback(
                    static fn (array $context): bool => isset($context['error'])
                        && $context['operation'] === 'cache.invalidation.error'
                )
            );

        $this->repository->deleteAll();
    }

    public function testMagicCallDelegatesToInnerRepository(): void
    {
        $id = $this->faker->uuid();
        $user = $this->createUserMock($id);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($user);

        $result = $this->repository->__call('findById', [$id]);

        self::assertSame($user, $result);
    }

    private function createUserMock(string $id, ?string $email = null): UserInterface&MockObject
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn($email ?? $this->faker->email());

        return $user;
    }
}
