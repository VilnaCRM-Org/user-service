# Complete Cache Implementation Example

Full working example of production-grade caching using **Decorator Pattern** and **Event-Driven Invalidation**.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│  Command Handler                                            │
│  └─ repository.save(customer)                               │
│  └─ eventBus.publish(CustomerUpdatedEvent)                  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  CachedCustomerRepository (Decorator)                       │
│  └─ inner.save(customer)  // delegates to Mongo             │
│  └─ (NO invalidation here - handled by events!)             │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  CustomerUpdatedCacheInvalidationSubscriber                 │
│  └─ cache.invalidateTags([...])  // event-driven!           │
└─────────────────────────────────────────────────────────────┘
```

## File Locations

| File                                | Location                                                                                       |
| ----------------------------------- | ---------------------------------------------------------------------------------------------- |
| CacheKeyBuilder                     | `src/Shared/Infrastructure/Cache/CacheKeyBuilder.php`                                          |
| CachedCustomerRepository            | `src/Core/Customer/Infrastructure/Repository/CachedCustomerRepository.php`                     |
| MongoCustomerRepository             | `src/Core/Customer/Infrastructure/Repository/MongoCustomerRepository.php`                      |
| CustomerCreatedCacheInvalidationSub | `src/Core/Customer/Application/EventSubscriber/CustomerCreatedCacheInvalidationSubscriber.php` |
| CustomerUpdatedCacheInvalidationSub | `src/Core/Customer/Application/EventSubscriber/CustomerUpdatedCacheInvalidationSubscriber.php` |
| CustomerDeletedCacheInvalidationSub | `src/Core/Customer/Application/EventSubscriber/CustomerDeletedCacheInvalidationSubscriber.php` |

---

## 1. CacheKeyBuilder Service

**Location**: `src/Shared/Infrastructure/Cache/CacheKeyBuilder.php`

```php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

/**
 * Cache Key Builder Service
 *
 * Responsibilities:
 * - Centralized cache key generation
 * - Consistent email hashing strategy
 * - Eliminates duplication across repository and event handlers
 */
final readonly class CacheKeyBuilder
{
    public function build(string $namespace, string ...$parts): string
    {
        return $namespace . '.' . implode('.', $parts);
    }

    public function buildCustomerKey(string $customerId): string
    {
        return $this->build('customer', $customerId);
    }

    public function buildCustomerEmailKey(string $email): string
    {
        return $this->build('customer', 'email', $this->hashEmail($email));
    }

    /**
     * Build cache key for collections (filters normalized + hashed)
     * @param array<string, string|int|float|bool|array|null> $filters
     */
    public function buildCustomerCollectionKey(array $filters): string
    {
        ksort($filters);
        return $this->build(
            'customer',
            'collection',
            hash('sha256', json_encode($filters, \JSON_THROW_ON_ERROR))
        );
    }

    /**
     * Hash email consistently (lowercase + SHA256)
     */
    public function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
```

---

## 2. Cached Repository Decorator

**Location**: `src/Core/Customer/Infrastructure/Repository/CachedCustomerRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Cached Customer Repository Decorator
 *
 * Responsibilities:
 * - Read-through caching with Stale-While-Revalidate (SWR)
 * - Cache key management via CacheKeyBuilder
 * - Graceful fallback to database on cache errors
 * - Delegates ALL persistence operations to inner repository
 *
 * Cache Invalidation:
 * - Handled by *CacheInvalidationSubscriber classes via domain events
 * - This class only reads from cache, never invalidates (except delete)
 */
final class CachedCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private CustomerRepositoryInterface $inner,
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {}

    /**
     * Proxy all other method calls to inner repository
     * Required for API Platform's collection provider compatibility
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }

    /**
     * Cache Policy: find by ID
     *
     * Key Pattern: customer.{id}
     * TTL: 600s (10 minutes)
     * Consistency: Stale-While-Revalidate (beta: 1.0)
     * Invalidation: Via domain events
     * Tags: [customer, customer.{id}]
     */
    public function find(mixed $id, int $lockMode = 0, ?int $lockVersion = null): ?Customer
    {
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey((string) $id);

        try {
            return $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadCustomerFromDb($id, $lockMode, $lockVersion, $cacheKey, $item),
                beta: 1.0  // Enable Stale-While-Revalidate
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $this->inner->find($id, $lockMode, $lockVersion);
        }
    }

    /**
     * Cache Policy: findByEmail
     *
     * Key Pattern: customer.email.{hash}
     * TTL: 300s (5 minutes)
     * Tags: [customer, customer.email, customer.email.{hash}]
     */
    public function findByEmail(string $email): ?Customer
    {
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);

        try {
            return $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadCustomerByEmail($email, $cacheKey, $item)
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $this->inner->findByEmail($email);
        }
    }

    public function save(Customer $customer): void
    {
        $this->inner->save($customer);
        // NO cache invalidation here - handled by domain event subscribers
    }

    public function delete(Customer $customer): void
    {
        $this->inner->delete($customer);
        // NO cache invalidation here - handled by CustomerDeletedEvent subscriber
    }

    private function loadCustomerFromDb(mixed $id, int $lockMode, ?int $lockVersion, string $cacheKey, ItemInterface $item): ?Customer
    {
        $item->expiresAfter(600);  // 10 minutes TTL
        $item->tag(['customer', "customer.{$id}"]);

        $this->logger->info('Cache miss - loading customer from database', [
            'cache_key' => $cacheKey,
            'customer_id' => $id,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->find($id, $lockMode, $lockVersion);
    }

    private function loadCustomerByEmail(string $email, string $cacheKey, ItemInterface $item): ?Customer
    {
        $item->expiresAfter(300);  // 5 minutes TTL
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $item->tag(['customer', 'customer.email', "customer.email.{$emailHash}"]);

        $this->logger->info('Cache miss - loading customer by email', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->findByEmail($email);
    }

    private function logCacheError(string $cacheKey, \Throwable $e): void
    {
        $this->logger->error('Cache error - falling back to database', [
            'cache_key' => $cacheKey,
            'error' => $e->getMessage(),
            'operation' => 'cache.error',
        ]);
    }
}
```

---

## 3. Cache Invalidation Event Subscribers

### CustomerUpdatedCacheInvalidationSubscriber (handles email changes)

**Location**: `src/Core/Customer/Application/EventSubscriber/CustomerUpdatedCacheInvalidationSubscriber.php`

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Updated Event Cache Invalidation Subscriber
 * Handles email change edge case (both old and new email caches)
 */
final readonly class CustomerUpdatedCacheInvalidationSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CustomerUpdatedEvent $event): void
    {
        // Best-effort: don't fail business operation if cache is down
        try {
            $tagsToInvalidate = $this->buildTagsToInvalidate($event);
            $this->cache->invalidateTags($tagsToInvalidate);
            $this->logSuccess($event);
        } catch (\Throwable $e) {
            $this->logError($event, $e);
        }
    }

    /** @return array<class-string> */
    public function subscribedTo(): array
    {
        return [CustomerUpdatedEvent::class];
    }

    /** @return array<string> */
    private function buildTagsToInvalidate(CustomerUpdatedEvent $event): array
    {
        $tags = [
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->currentEmail()),
            'customer.collection',
        ];

        // CRITICAL: If email changed, invalidate previous email cache too!
        if ($event->emailChanged() && $event->previousEmail() !== null) {
            $tags[] = 'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->previousEmail());
        }

        return $tags;
    }

    private function logSuccess(CustomerUpdatedEvent $event): void
    {
        $this->logger->info('Cache invalidated after customer update', [
            'customer_id' => $event->customerId(),
            'email_changed' => $event->emailChanged(),
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_updated',
        ]);
    }

    private function logError(CustomerUpdatedEvent $event, \Throwable $e): void
    {
        $this->logger->error('Cache invalidation failed after customer update', [
            'customer_id' => $event->customerId(),
            'event_id' => $event->eventId(),
            'error' => $e->getMessage(),
            'operation' => 'cache.invalidation.error',
        ]);
    }
}
```

### CustomerCreatedCacheInvalidationSubscriber

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class CustomerCreatedCacheInvalidationSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CustomerCreatedEvent $event): void
    {
        try {
            $this->cache->invalidateTags([
                'customer.' . $event->customerId(),
                'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->customerEmail()),
                'customer.collection',
            ]);
            $this->logger->info('Cache invalidated after customer creation', [
                'customer_id' => $event->customerId(),
                'event_id' => $event->eventId(),
                'operation' => 'cache.invalidation',
                'reason' => 'customer_created',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Cache invalidation failed after customer creation', [
                'customer_id' => $event->customerId(),
                'event_id' => $event->eventId(),
                'error' => $e->getMessage(),
                'operation' => 'cache.invalidation.error',
            ]);
        }
    }

    /** @return array<class-string> */
    public function subscribedTo(): array
    {
        return [CustomerCreatedEvent::class];
    }
}
```

---

## 4. services.yaml Configuration

**Location**: `config/services.yaml`

```yaml
services:
  # Base repository - used by API Platform for collections
  App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository:
    public: true

  # Cached repository - wraps base repository with caching
  App\Core\Customer\Infrastructure\Repository\CachedCustomerRepository:
    arguments:
      $inner: '@App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository'
      $cache: '@cache.customer'

  # Alias interface to cached repository for dependency injection
  App\Core\Customer\Domain\Repository\CustomerRepositoryInterface:
    alias: App\Core\Customer\Infrastructure\Repository\CachedCustomerRepository
    public: true

  # Cache invalidation event subscribers - explicitly inject cache.customer
  App\Core\Customer\Application\EventSubscriber\CustomerCreatedCacheInvalidationSubscriber:
    arguments:
      $cache: '@cache.customer'

  App\Core\Customer\Application\EventSubscriber\CustomerUpdatedCacheInvalidationSubscriber:
    arguments:
      $cache: '@cache.customer'

  App\Core\Customer\Application\EventSubscriber\CustomerDeletedCacheInvalidationSubscriber:
    arguments:
      $cache: '@cache.customer'
```

---

## 5. Cache Pool Configuration

**Production** - `config/packages/cache.yaml`:

```yaml
framework:
  cache:
    app: cache.adapter.redis
    default_redis_provider: '%env(resolve:REDIS_URL)%'

    pools:
      app:
        adapter: cache.adapter.redis
        default_lifetime: 86400 # 24 hours
        provider: '%env(resolve:REDIS_URL)%'
      cache.customer:
        adapter: cache.adapter.redis
        default_lifetime: 600 # 10 minutes
        provider: '%env(resolve:REDIS_URL)%'
        tags: true # REQUIRED for TagAwareCacheInterface
```

**Test** - `config/packages/test/cache.yaml`:

```yaml
framework:
  cache:
    app: cache.adapter.array
    default_redis_provider: null
    pools:
      app:
        adapter: cache.adapter.array
        provider: null
      cache.customer:
        adapter: cache.adapter.array
        provider: null
        tags: true # CRITICAL: Must have tags: true for TagAwareCacheInterface!
```

---

## Summary

This complete example demonstrates:

✅ **Decorator pattern** - `CachedCustomerRepository` wraps `MongoCustomerRepository`
✅ **Event-driven invalidation** - Subscribers handle invalidation via domain events
✅ **Best-effort invalidation** - try/catch prevents cache failures from breaking business operations
✅ **CacheKeyBuilder service** - Centralized key generation, no drift
✅ **Cache policies declared** for each query (TTL, tags, SWR)
✅ **Email change handling** - Invalidates both old and new email caches
✅ **Observability** (structured logs for hit/miss/error)
✅ **Graceful fallback** to database on cache errors
✅ **API Platform compatibility** via `__call()` magic method

**Key Architecture Decisions**:

1. **Decorator Pattern**: Separation of caching from persistence logic
2. **Event-Driven Invalidation**: Decoupled from repository, testable, flexible
3. **Best-Effort**: Cache failures never break business operations
4. **Centralized Key Building**: Prevents key drift across services
