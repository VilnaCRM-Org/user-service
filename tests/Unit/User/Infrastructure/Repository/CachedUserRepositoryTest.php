<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Infrastructure\Repository\CachedUserRepository;
use App\User\Infrastructure\Repository\MariaDBUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

final class CachedUserRepositoryTest extends UnitTestCase
{
    private MariaDBUserRepository&MockObject $innerRepository;
    private TagAwareAdapterInterface&MockObject $cache;
    private CacheKeyBuilder&MockObject $cacheKeyBuilder;
    private LoggerInterface&MockObject $logger;
    private EntityManagerInterface&MockObject $entityManager;
    private UnitOfWork&MockObject $unitOfWork;
    private CachedUserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = $this->createMock(MariaDBUserRepository::class);
        $this->cache = $this->createMock(TagAwareAdapterInterface::class);
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->repository = new CachedUserRepository(
            $this->innerRepository,
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger,
            $this->entityManager
        );
    }

    public function testFindReturnsFreshEntityFromDatabaseWhenCacheHitButEntityNotManaged(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $cachedUser = $this->createUserMock($userId);
        $freshUser = $this->createUserMock($userId);

        $this->setupCacheKeyBuilder($userId);
        $this->setupCacheHit($cacheKey, $cachedUser);
        $this->setupUnitOfWorkWithNoManagedEntity($userId);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($freshUser);

        $result = $this->repository->find($userId);

        self::assertSame($freshUser, $result);
    }

    public function testFindReturnsCachedEntityWhenDatabaseFetchFails(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $cachedUser = $this->createUserMock($userId);

        $this->setupCacheKeyBuilder($userId);
        $this->setupCacheHit($cacheKey, $cachedUser);
        $this->setupUnitOfWorkWithNoManagedEntity($userId);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        $result = $this->repository->find($userId);

        self::assertSame($cachedUser, $result);
    }

    public function testFindReturnsManagedEntityWhenCacheHitButEntityIsManaged(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $cachedUser = $this->createUserMock($userId);
        $managedUser = $this->createUserMock($userId);

        $this->setupCacheKeyBuilder($userId);
        $this->setupCacheHit($cacheKey, $cachedUser);
        $this->setupUnitOfWorkWithManagedEntity($userId, $managedUser);

        $result = $this->repository->find($userId);

        self::assertSame($managedUser, $result);
    }

    public function testFindCacheMissLoadsFromDatabaseAndCaches(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $user = $this->createUserMock($userId);

        $this->setupCacheKeyBuilder($userId);
        $cacheItem = $this->setupCacheMiss($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId, null, null)
            ->willReturn($user);

        $cacheItem->expects($this->once())->method('set')->with($user);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(600);
        $cacheItem->expects($this->once())->method('tag')->with(['user', "user.{$userId}"]);

        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $result = $this->repository->find($userId);

        self::assertSame($user, $result);
    }

    public function testFindCacheMissWithNullUserDoesNotCache(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;

        $this->setupCacheKeyBuilder($userId);
        $cacheItem = $this->setupCacheMiss($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId, null, null)
            ->willReturn(null);

        $cacheItem->expects($this->never())->method('set');
        $this->cache->expects($this->never())->method('save');

        $result = $this->repository->find($userId);

        self::assertNull($result);
    }

    public function testFindFallsBackToDatabaseOnCacheError(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $user = $this->createUserMock($userId);

        $this->setupCacheKeyBuilder($userId);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(static fn ($context) => $context['cache_key'] === $cacheKey
                    && $context['error'] === 'Cache unavailable')
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId, null, null)
            ->willReturn($user);

        $result = $this->repository->find($userId);

        self::assertSame($user, $result);
    }

    public function testFindByIdReattachesDetachedEntityFromCache(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $cachedUser = $this->createMock(UserInterface::class);
        $cachedUser->method('getId')->willReturn($userId);
        $freshUser = $this->createMock(UserInterface::class);

        $this->setupCacheKeyBuilder($userId);
        $this->setupCacheHit($cacheKey, $cachedUser);
        $this->setupUnitOfWorkWithNoManagedEntity($userId);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($freshUser);

        $result = $this->repository->findById($userId);

        self::assertSame($freshUser, $result);
    }

    public function testFindByIdReturnsNullWhenCacheContainsNonUserValue(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $cachedValue = false;

        $this->setupCacheKeyBuilder($userId);
        $this->setupCacheHit($cacheKey, $cachedValue);

        $result = $this->repository->findById($userId);

        self::assertNull($result);
    }

    public function testFindByIdCacheMissLoadsFromDatabase(): void
    {
        $userId = $this->faker->uuid();
        $cacheKey = 'user.' . $userId;
        $user = $this->createMock(UserInterface::class);

        $this->setupCacheKeyBuilder($userId);
        $cacheItem = $this->setupCacheMiss($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $cacheItem->expects($this->once())->method('set')->with($user);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $result = $this->repository->findById($userId);

        self::assertSame($user, $result);
    }

    public function testFindByIdFallsBackToDatabaseOnCacheError(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createMock(UserInterface::class);

        $this->setupCacheKeyBuilder($userId);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger->expects($this->once())->method('error');

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->repository->findById($userId);

        self::assertSame($user, $result);
    }

    public function testFindByEmailReattachesDetachedEntityFromCache(): void
    {
        $email = $this->faker->email();
        $userId = $this->faker->uuid();
        $cacheKey = 'user.email.hash123';
        $cachedUser = $this->createMock(UserInterface::class);
        $cachedUser->method('getId')->willReturn($userId);
        $freshUser = $this->createMock(UserInterface::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->setupCacheHit($cacheKey, $cachedUser);
        $this->setupUnitOfWorkWithNoManagedEntity($userId);

        $this->innerRepository
            ->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($freshUser);

        $result = $this->repository->findByEmail($email);

        self::assertSame($freshUser, $result);
    }

    public function testFindByEmailCacheMissLoadsFromDatabase(): void
    {
        $email = $this->faker->email();
        $emailHash = 'hash_abc123';
        $cacheKey = 'user.email.' . $emailHash;
        $user = $this->createMock(UserInterface::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $cacheItem = $this->setupCacheMiss($cacheKey);

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $cacheItem->expects($this->once())->method('set')->with($user);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(300);
        $cacheItem->expects($this->once())->method('tag')
            ->with(['user', 'user.email', "user.email.{$emailHash}"]);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $result = $this->repository->findByEmail($email);

        self::assertSame($user, $result);
    }

    public function testFindByEmailFallsBackToDatabaseOnCacheError(): void
    {
        $email = $this->faker->email();
        $cacheKey = 'user.email.hash123';
        $user = $this->createMock(UserInterface::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger->expects($this->once())->method('error');

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->repository->findByEmail($email);

        self::assertSame($user, $result);
    }

    public function testSaveDelegatesToInnerRepositoryAndInvalidatesCache(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $emailHash = 'hash123';
        $user = $this->createUserMock($userId, $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(["user.{$userId}", "user.email.{$emailHash}"]);

        $this->repository->save($user);
    }

    public function testDeleteDelegatesToInnerRepositoryAndInvalidatesCache(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $emailHash = 'hash123';
        $user = $this->createUserMock($userId, $email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->innerRepository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(["user.{$userId}", "user.email.{$emailHash}"]);

        $this->repository->delete($user);
    }

    public function testSaveBatchDelegatesToInnerRepositoryAndInvalidatesCacheForEachUser(): void
    {
        $user1 = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $user2 = $this->createUserMock($this->faker->uuid(), $this->faker->email());
        $users = [$user1, $user2];

        $this->cacheKeyBuilder
            ->expects($this->exactly(2))
            ->method('hashEmail')
            ->willReturn('hash1', 'hash2');

        $this->innerRepository
            ->expects($this->once())
            ->method('saveBatch')
            ->with($users);

        $this->cache
            ->expects($this->exactly(2))
            ->method('invalidateTags');

        $this->repository->saveBatch($users);
    }

    public function testDeleteAllDelegatesToInnerRepository(): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('deleteAll');

        $this->repository->deleteAll();
    }

    public function testMagicCallDelegatesToInnerRepository(): void
    {
        $className = User::class;

        $this->innerRepository
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $result = $this->repository->getClassName();

        self::assertSame($className, $result);
    }

    public function testSaveHandlesCacheInvalidationError(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $user = $this->createUserMock($userId, $email);

        $this->cacheKeyBuilder
            ->method('hashEmail')
            ->willReturn('hash123');

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willThrowException(new \RuntimeException('Cache invalidation failed'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to invalidate cache after save/delete',
                $this->callback(static fn ($context) => $context['user_id'] === $userId
                    && str_contains($context['error'], 'Cache invalidation failed'))
            );

        $this->repository->save($user);
    }

    private function createUserMock(string $userId, ?string $email = null): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn($email ?? $this->faker->email());
        return $user;
    }

    private function setupCacheKeyBuilder(string $userId): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildUserKey')
            ->with($userId)
            ->willReturn('user.' . $userId);
    }

    private function setupCacheHit(string $cacheKey, mixed $value): void
    {
        $cacheItem = $this->createMock(CacheItem::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($value);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem);
    }

    private function setupCacheMiss(string $cacheKey): CacheItem&MockObject
    {
        $cacheItem = $this->createMock(CacheItem::class);
        $cacheItem->method('isHit')->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem);

        return $cacheItem;
    }

    private function setupUnitOfWorkWithNoManagedEntity(string $userId): void
    {
        $this->unitOfWork
            ->method('tryGetById')
            ->with($userId, User::class)
            ->willReturn(false);
    }

    private function setupUnitOfWorkWithManagedEntity(string $userId, User $managedUser): void
    {
        $this->unitOfWork
            ->method('tryGetById')
            ->with($userId, User::class)
            ->willReturn($managedUser);
    }
}
