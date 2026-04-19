<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Collection\UserCollection;
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
final class CachedUserRepository extends UserRepositoryDecorator
{
    private const TTL_BY_ID = 600;
    private const TTL_BY_EMAIL = 300;

    public function __construct(
        UserRepositoryInterface $inner,
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger,
        private DocumentManager $documentManager
    ) {
        parent::__construct($inner);
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
     *
     * @param numeric-string $id
     */
    public function find(
        string $id,
        ?int $_lockMode = null,
        ?int $_lockVersion = null
    ): ?UserInterface {
        // MongoDB ODM doesn't support lock modes, use findById instead
        return $this->findById($id);
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
     * Bulk lookups intentionally delegate to the inner repository because the
     * point-lookup cache is keyed per user and would not help this query shape.
     *
     * @param array<int, string> $emails
     */
    #[\Override]
    public function findByEmails(array $emails): UserCollection
    {
        return $this->inner->findByEmails($emails);
    }

    #[\Override]
    public function save(object $user): void
    {
        $this->inner->save($user);

        if ($user instanceof UserInterface) {
            $this->invalidateUserCache($user, 'save');
        }
    }

    #[\Override]
    public function delete(object $user): void
    {
        $this->inner->delete($user);

        if ($user instanceof UserInterface) {
            $this->invalidateUserCache($user, 'delete');
        }
    }

    #[\Override]
    public function saveBatch(UserCollection $users): void
    {
        $this->inner->saveBatch($users);
        $this->invalidateUserCollectionCache($users, 'save_batch');
    }

    #[\Override]
    public function deleteBatch(UserCollection $users): void
    {
        $this->inner->deleteBatch($users);
        $this->invalidateUserCollectionCache($users, 'delete_batch');
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

        if ($this->documentManager->contains($user)) {
            return $user;
        }

        return $this->reattachCachedUser($user, $cacheKey, $fallback);
    }

    /**
     * @param callable(): mixed $fallback
     */
    private function reattachCachedUser(
        UserInterface $cached,
        string $cacheKey,
        callable $fallback
    ): ?UserInterface
    {
        try {
            $managedUser = $this->documentManager->merge($cached);

            if ($managedUser instanceof UserInterface) {
                return $managedUser;
            }

            $this->logger->warning('Cache merge returned an unexpected value - falling back to database', [
                'cache_key' => $cacheKey,
                'user_id' => $cached->getId(),
                'operation' => 'cache.merge.invalid',
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to reattach cached user - falling back to database', [
                'cache_key' => $cacheKey,
                'user_id' => $cached->getId(),
                'error' => $e->getMessage(),
                'operation' => 'cache.merge.error',
            ]);
        }

        return $fallback();
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

    private function invalidateUserCache(UserInterface $user, string $operation): void
    {
        $this->invalidateTags([
            'user.collection',
            'user.' . $user->getId(),
            'user.email.' . $this->cacheKeyBuilder->hashEmail($user->getEmail()),
        ], $operation);
    }

    private function invalidateUserCollectionCache(
        UserCollection $users,
        string $operation
    ): void {
        $tags = ['user.collection'];

        foreach ($users as $user) {
            if (!$user instanceof UserInterface) {
                continue;
            }

            $tags[] = 'user.' . $user->getId();
            $tags[] = 'user.email.' . $this->cacheKeyBuilder->hashEmail($user->getEmail());
        }

        $this->invalidateTags($tags, $operation);
    }

    /**
     * @param list<string> $tags
     */
    private function invalidateTags(array $tags, string $operation): void
    {
        try {
            $this->cache->invalidateTags(array_values(array_unique($tags)));
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to invalidate cache after user write', [
                'error' => $e->getMessage(),
                'operation' => 'cache.invalidation.error',
                'write_operation' => $operation,
            ]);
        }
    }
}
