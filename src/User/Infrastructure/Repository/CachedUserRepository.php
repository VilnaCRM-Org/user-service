<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

final class CachedUserRepository implements UserRepositoryInterface
{
    private const int TTL_BY_ID = 600;
    private const int TTL_BY_EMAIL = 300;

    public function __construct(
        private UserRepositoryInterface $inner,
        private TagAwareAdapterInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }

    #[\Override]
    public function find(
        mixed $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    ): ?object {
        $cacheKey = $this->cacheKeyBuilder->buildUserKey((string) $id);
        $tags = ['user', "user.{$id}"];

        return $this->getFromCacheOrLoad(
            $cacheKey,
            fn () => $this->inner->find($id, $lockMode, $lockVersion),
            self::TTL_BY_ID,
            $tags,
            true
        );
    }

    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        $cacheKey = $this->cacheKeyBuilder->buildUserKey($id);
        $tags = ['user', "user.{$id}"];

        $result = $this->getFromCacheOrLoad(
            $cacheKey,
            fn () => $this->inner->findById($id),
            self::TTL_BY_ID,
            $tags,
            true
        );

        return $result instanceof UserInterface ? $result : null;
    }

    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        $cacheKey = $this->cacheKeyBuilder->buildUserEmailKey($email);
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $tags = ['user', 'user.email', "user.email.{$emailHash}"];

        $result = $this->getFromCacheOrLoad(
            $cacheKey,
            fn () => $this->inner->findByEmail($email),
            self::TTL_BY_EMAIL,
            $tags,
            true
        );

        return $result instanceof UserInterface ? $result : null;
    }

    /**
     * @param User $user
     */
    #[\Override]
    public function save(object $user): void
    {
        $this->inner->save($user);
        $this->invalidateUserCache($user);
    }

    /**
     * @param User $user
     */
    #[\Override]
    public function delete(object $user): void
    {
        $this->inner->delete($user);
        $this->invalidateUserCache($user);
    }

    /**
     * @param array<User> $users
     */
    #[\Override]
    public function saveBatch(array $users): void
    {
        $this->inner->saveBatch($users);
        array_walk($users, fn (User $user) => $this->invalidateUserCache($user));
    }

    #[\Override]
    public function deleteAll(): void
    {
        $this->inner->deleteAll();
    }

    /**
     * @param array<string> $tags
     */
    private function getFromCacheOrLoad(
        string $cacheKey,
        callable $loader,
        int $ttl,
        array $tags,
        bool $checkManagedEntity
    ): mixed {
        try {
            $item = $this->cache->getItem($cacheKey);

            if ($item->isHit()) {
                return $this->handleCacheHit($cacheKey, $item, $checkManagedEntity);
            }

            return $this->handleCacheMiss($cacheKey, $item, $loader, $ttl, $tags);
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $loader();
        }
    }

    private function handleCacheHit(
        string $cacheKey,
        CacheItem $item,
        bool $checkManagedEntity
    ): mixed {
        $this->logCacheHit($cacheKey);
        $cached = $item->get();

        return $checkManagedEntity ? $this->getManagedEntityIfExists($cached) : $cached;
    }

    /**
     * @param array<string> $tags
     */
    private function handleCacheMiss(
        string $cacheKey,
        CacheItem $item,
        callable $loader,
        int $ttl,
        array $tags
    ): mixed {
        $value = $loader();

        if ($value !== null) {
            $this->saveToCache($item, $value, $ttl, $tags);
        }

        $this->logCacheMiss($cacheKey, $value !== null);
        return $value;
    }

    /**
     * @param array<string> $tags
     */
    private function saveToCache(CacheItem $item, mixed $value, int $ttl, array $tags): void
    {
        $item->set($value);
        $item->expiresAfter($ttl);
        $item->tag($tags);
        $this->cache->save($item);
    }

    private function getManagedEntityIfExists(mixed $cached): mixed
    {
        if (!$cached instanceof User && !$cached instanceof UserInterface) {
            return $cached;
        }

        $managed = $this->entityManager
            ->getUnitOfWork()
            ->tryGetById($cached->getId(), User::class);

        if ($managed instanceof User) {
            return $managed;
        }

        return $this->inner->findById($cached->getId()) ?? $cached;
    }

    private function invalidateUserCache(User $user): void
    {
        try {
            $this->cache->invalidateTags([
                'user.' . $user->getId(),
                'user.email.' . $this->cacheKeyBuilder->hashEmail($user->getEmail()),
            ]);
        } catch (\Throwable $e) {
            $this->logCacheInvalidationError($user->getId(), $e);
        }
    }

    private function logCacheHit(string $cacheKey): void
    {
        $this->logger->info('Cache hit', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.hit',
        ]);
    }

    private function logCacheMiss(string $cacheKey, bool $found): void
    {
        $this->logger->info('Cache miss - loaded from database', [
            'cache_key' => $cacheKey,
            'found' => $found,
            'operation' => 'cache.miss',
        ]);
    }

    private function logCacheError(string $cacheKey, \Throwable $e): void
    {
        $this->logger->error('Cache error - falling back to database', [
            'cache_key' => $cacheKey,
            'error' => $e->getMessage(),
            'operation' => 'cache.error',
        ]);
    }

    private function logCacheInvalidationError(string $userId, \Throwable $e): void
    {
        $this->logger->warning('Failed to invalidate cache after save/delete', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
            'operation' => 'cache.invalidation.error',
        ]);
    }
}
