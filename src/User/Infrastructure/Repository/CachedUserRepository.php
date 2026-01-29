<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
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
 * - Wraps MongoDBUserRepository
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
        private DocumentManager $documentManager
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
     * Note: This is a Doctrine method, not part of UserRepositoryInterface.
     * MongoDB ODM doesn't support lock modes, so we delegate to findById for caching.
     */
    public function find(
        mixed $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    ): ?object {
        // MongoDB ODM doesn't support lock modes, use findById instead
        return $this->findById((string) $id);
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

        return $this->fetchUser(
            $cacheKey,
            fn (ItemInterface $item) => $this->loadUserByIdFromDb($id, $cacheKey, $item),
            fn () => $this->inner->findById($id),
            1.0
        );
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

        return $this->fetchUser(
            $cacheKey,
            fn (ItemInterface $item) => $this->loadUserByEmailFromDb($email, $cacheKey, $item),
            fn () => $this->inner->findByEmail($email)
        );
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

    /**
     * @param callable(ItemInterface): mixed $cacheLoader
     * @param callable(): mixed $fallback
     */
    private function fetchUser(
        string $cacheKey,
        callable $cacheLoader,
        callable $fallback,
        ?float $beta = null
    ): ?UserInterface {
        try {
            $user = $this->cache->get($cacheKey, $cacheLoader, $beta);

            return $this->normalizeCachedUser($user, $cacheKey, $fallback);
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);

            return $fallback();
        }
    }

    /**
     * @param callable(): mixed $fallback
     */
    private function normalizeCachedUser(
        mixed $user,
        string $cacheKey,
        callable $fallback
    ): ?UserInterface {
        if (!$user instanceof UserInterface) {
            $this->cache->delete($cacheKey);

            return $fallback();
        }

        return $this->getManagedDocumentIfExists($user) ?? $user;
    }

    /**
     * Get managed MongoDB document if it exists in the DocumentManager's unit of work.
     *
     * MongoDB ODM doesn't have tryGetById like ORM, so we check if the document is managed.
     */
    private function getManagedDocumentIfExists(UserInterface $cached): ?UserInterface
    {
        // Check if document is already managed by DocumentManager
        if ($this->documentManager->contains($cached)) {
            return $cached;
        }

        // Refresh from database to get managed instance
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
