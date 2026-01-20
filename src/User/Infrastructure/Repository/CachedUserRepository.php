<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Cached User Repository Decorator
 *
 * Responsibilities:
 * - Read-through caching with Stale-While-Revalidate (SWR)
 * - Cache key management via CacheKeyBuilder
 * - Cache hit/miss logging for observability
 * - Graceful fallback to database on cache errors
 * - Delegates ALL persistence operations to inner repository
 *
 * Decorator Pattern:
 * - Wraps MariaDBUserRepository
 * - Adds caching layer without modifying persistence logic
 * - Transparent to consumers (implements same interface)
 *
 * Cache Invalidation:
 * - Handled by UserCacheInvalidationSubscriber via domain events
 * - This class only reads from cache, never invalidates
 */
final class CachedUserRepository implements UserRepositoryInterface
{
    private const TTL_BY_ID = 600;
    private const TTL_BY_EMAIL = 300;

    public function __construct(
        private UserRepositoryInterface $inner,
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Proxy all other method calls to inner repository
     *
     * This ensures compatibility with API Platform's collection provider
     * which may call Doctrine repository methods not in our interface.
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }

    /**
     * Cache Policy: find by ID
     *
     * Key Pattern: user.{id}
     * TTL: 600s (10 minutes)
     * Consistency: Stale-While-Revalidate (beta: 1.0)
     * Invalidation: Via UserCacheInvalidationSubscriber on update/delete
     * Tags: [user, user.{id}]
     * Notes: Read-heavy operation, tolerates brief staleness
     */
    #[\Override]
    public function find(
        mixed $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    ): ?object {
        $cacheKey = $this->cacheKeyBuilder->buildUserKey((string) $id);

        try {
            $user = $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadUserFromDb(
                    $id,
                    $lockMode,
                    $lockVersion,
                    $cacheKey,
                    $item
                ),
                beta: 1.0  // Enable Stale-While-Revalidate
            );

            // Check if entity is already managed by Doctrine
            if ($user instanceof User || $user instanceof UserInterface) {
                return $this->getManagedEntityIfExists($user) ?? $user;
            }

            $this->cache->delete($cacheKey);
            return $this->inner->find($id, $lockMode, $lockVersion);
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $this->inner->find($id, $lockMode, $lockVersion);
        }
    }

    /**
     * Cache Policy: findById
     *
     * Key Pattern: user.{id}
     * TTL: 600s (10 minutes)
     * Consistency: Stale-While-Revalidate (beta: 1.0)
     * Invalidation: Via UserCacheInvalidationSubscriber on update/delete
     * Tags: [user, user.{id}]
     * Notes: Read-heavy operation, tolerates brief staleness
     */
    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        $cacheKey = $this->cacheKeyBuilder->buildUserKey($id);

        try {
            $user = $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadUserByIdFromDb($id, $cacheKey, $item),
                beta: 1.0  // Enable Stale-While-Revalidate
            );

            if (!($user instanceof UserInterface)) {
                $this->cache->delete($cacheKey);
                return $this->inner->findById($id);
            }

            // Check if entity is already managed by Doctrine
            return $this->getManagedEntityIfExists($user) ?? $user;
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $this->inner->findById($id);
        }
    }

    /**
     * Cache Policy: findByEmail
     *
     * Key Pattern: user.email.{hash}
     * TTL: 300s (5 minutes)
     * Consistency: Eventual
     * Invalidation: Via UserCacheInvalidationSubscriber on email change
     * Tags: [user, user.email, user.email.{hash}]
     * Notes: Common authentication/lookup operation
     */
    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        $cacheKey = $this->cacheKeyBuilder->buildUserEmailKey($email);

        try {
            $user = $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadUserByEmailFromDb($email, $cacheKey, $item)
            );

            if (!($user instanceof UserInterface)) {
                $this->cache->delete($cacheKey);
                return $this->inner->findByEmail($email);
            }

            // Check if entity is already managed by Doctrine
            return $this->getManagedEntityIfExists($user) ?? $user;
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $this->inner->findByEmail($email);
        }
    }

    /**
     * Delegate persistence to inner repository (no caching on writes)
     *
     * @param User $user
     */
    #[\Override]
    public function save(object $user): void
    {
        $this->inner->save($user);
    }

    /**
     * Delegate deletion to inner repository (no invalidation here)
     *
     * Cache invalidation is handled via UserDeletedEvent subscribers.
     *
     * @param User $user
     */
    #[\Override]
    public function delete(object $user): void
    {
        $this->inner->delete($user);
    }

    /**
     * Delegate batch save to inner repository (no caching on writes)
     *
     * @param array<User> $users
     */
    #[\Override]
    public function saveBatch(array $users): void
    {
        $this->inner->saveBatch($users);
    }

    /**
     * Delegate batch delete to inner repository (no invalidation here)
     *
     * Cache invalidation is handled via UserDeletedEvent subscribers.
     *
     * @param array<User> $users
     */
    #[\Override]
    public function deleteBatch(array $users): void
    {
        $this->inner->deleteBatch($users);
    }

    #[\Override]
    public function deleteAll(): void
    {
        $this->inner->deleteAll();
        try {
            $this->cache->invalidateTags(['user', 'user.collection']);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to invalidate cache after deleteAll', [
                'error' => $e->getMessage(),
                'operation' => 'cache.invalidation.error',
            ]);
        }
    }

    /**
     * Load user from database and configure cache item
     */
    private function loadUserFromDb(
        mixed $id,
        ?int $lockMode,
        ?int $lockVersion,
        string $cacheKey,
        ItemInterface $item
    ): ?object {
        $item->expiresAfter(self::TTL_BY_ID);  // 10 minutes TTL
        $item->tag(['user', "user.{$id}"]);

        $this->logger->info('Cache miss - loading user from database', [
            'cache_key' => $cacheKey,
            'user_id' => $id,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->find($id, $lockMode, $lockVersion);
    }

    /**
     * Load user by ID from database and configure cache item
     */
    private function loadUserByIdFromDb(
        string $id,
        string $cacheKey,
        ItemInterface $item
    ): ?UserInterface {
        $item->expiresAfter(self::TTL_BY_ID);  // 10 minutes TTL
        $item->tag(['user', "user.{$id}"]);

        $this->logger->info('Cache miss - loading user by ID from database', [
            'cache_key' => $cacheKey,
            'user_id' => $id,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->findById($id);
    }

    /**
     * Load user by email from database and configure cache item
     */
    private function loadUserByEmailFromDb(
        string $email,
        string $cacheKey,
        ItemInterface $item
    ): ?UserInterface {
        $item->expiresAfter(self::TTL_BY_EMAIL);  // 5 minutes TTL
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $item->tag([
            'user',
            'user.email',
            "user.email.{$emailHash}",
        ]);

        $this->logger->info('Cache miss - loading user by email', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->findByEmail($email);
    }

    private function getManagedEntityIfExists(UserInterface $cached): ?UserInterface
    {
        $managed = $this->entityManager
            ->getUnitOfWork()
            ->tryGetById($cached->getId(), User::class);

        if ($managed instanceof User) {
            return $managed;
        }

        return $this->inner->findById($cached->getId());
    }

    /**
     * Log cache errors for observability
     */
    private function logCacheError(string $cacheKey, \Throwable $e): void
    {
        $this->logger->error('Cache error - falling back to database', [
            'cache_key' => $cacheKey,
            'error' => $e->getMessage(),
            'operation' => 'cache.error',
        ]);
    }
}
