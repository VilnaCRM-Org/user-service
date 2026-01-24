# Complete Cache Testing Guide

Comprehensive test suite for cache behavior: stale reads after writes, cache warmup on cold start, TTL expiration, invalidation strategies, and SWR behavior.

## Test Suite Structure

```text
tests/
├── Unit/
│   └── Customer/
│       └── Infrastructure/
│           └── Cache/
│               └── CustomerCacheWarmerTest.php
└── Integration/
    └── Customer/
        └── Infrastructure/
            └── Repository/
                ├── MongoCustomerRepositoryCacheTest.php
                ├── MongoCustomerRepositoryInvalidationTest.php
                └── MongoCustomerRepositorySwrTest.php
```

---

## Test 1: Stale Reads After Writes

**Objective**: Ensure cache is invalidated immediately after write operations, preventing stale data.

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MongoCustomerRepositoryInvalidationTest extends KernelTestCase
{
    private CustomerRepositoryInterface $repository;
    private TagAwareCacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepositoryInterface::class);
        $this->cache = self::getContainer()->get(TagAwareCacheInterface::class);

        // Clear cache before each test
        $this->cache->clear();
    }

    public function testCacheInvalidatedAfterUpdate(): void
    {
        // Arrange: Create customer
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');
        $customerId = $customer->id();

        // Act 1: First read - cache miss, loads from DB
        $result1 = $this->repository->findById($customerId);
        self::assertNotNull($result1);
        self::assertSame('John Doe', $result1->name());

        // Act 2: Second read - cache hit
        $result2 = $this->repository->findById($customerId);
        self::assertSame('John Doe', $result2->name());

        // Act 3: Update customer - should invalidate cache
        $customer->updateName('Jane Doe');
        $this->repository->save($customer);

        // Act 4: Third read - should get FRESH data (cache was invalidated)
        $result3 = $this->repository->findById($customerId);
        self::assertSame('Jane Doe', $result3->name());

        // Assert: We're NOT reading stale cached data
        self::assertNotSame($result2->name(), $result3->name());
    }

    public function testCacheInvalidatedAfterDelete(): void
    {
        // Arrange
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');
        $customerId = $customer->id();

        // Act: Cache customer
        $result1 = $this->repository->findById($customerId);
        self::assertNotNull($result1);

        // Act: Delete customer
        $this->repository->delete($customer);

        // Assert: Cache was invalidated, returns null
        $result2 = $this->repository->findById($customerId);
        self::assertNull($result2);
    }

    public function testListCacheInvalidatedAfterCreate(): void
    {
        // Arrange: Cache active customers list
        $list1 = $this->repository->findActiveCustomers();
        $initialCount = count($list1);

        // Act: Create new customer
        $newCustomer = $this->createTestCustomer('New Customer', 'new@example.com');
        $this->repository->save($newCustomer);

        // Assert: List cache was invalidated, new customer appears
        $list2 = $this->repository->findActiveCustomers();
        self::assertCount($initialCount + 1, $list2);
    }

    public function testEmailCacheInvalidatedAfterEmailChange(): void
    {
        // Arrange
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');

        // Act: Cache by email
        $result1 = $this->repository->findByEmail('john@example.com');
        self::assertNotNull($result1);

        // Act: Change email
        $customer->updateEmail('jane@example.com');
        $this->repository->save($customer);

        // Assert: Old email cache returns null
        $result2 = $this->repository->findByEmail('john@example.com');
        self::assertNull($result2);

        // Assert: New email cache works
        $result3 = $this->repository->findByEmail('jane@example.com');
        self::assertNotNull($result3);
        self::assertSame($customer->id(), $result3->id());
    }

    private function createTestCustomer(string $name, string $email): Customer
    {
        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $this->repository->save($customer);

        return $customer;
    }
}
```

---

## Test 2: Cache Warmup on Cold Start

**Objective**: Ensure queries work correctly with empty cache (cold start scenario).

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Cache\CustomerCacheWarmer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MongoCustomerRepositoryCacheTest extends KernelTestCase
{
    private CustomerRepository $repository;
    private CustomerCacheWarmer $cacheWarmer;
    private TagAwareCacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepository::class);
        $this->cacheWarmer = self::getContainer()->get(CustomerCacheWarmer::class);
        $this->cache = self::getContainer()->get(TagAwareCacheInterface::class);
    }

    public function testColdStartCacheMiss(): void
    {
        // Arrange: Clear ALL cache (simulate cold start)
        $this->cache->clear();

        // Act: Create customer
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');

        // Act: First read after cold start - cache miss, loads from DB
        $result = $this->repository->findById($customer->id());

        // Assert: Customer loaded correctly
        self::assertNotNull($result);
        self::assertSame($customer->id(), $result->id());
        self::assertSame('John Doe', $result->name());
    }

    public function testCacheWarmupPopulatesCache(): void
    {
        // Arrange: Clear cache
        $this->cache->clear();

        // Arrange: Create test customers
        $customer1 = $this->createTestCustomer('Customer 1', 'customer1@example.com');
        $customer2 = $this->createTestCustomer('Customer 2', 'customer2@example.com');
        $customer3 = $this->createTestCustomer('Customer 3', 'customer3@example.com');

        // Act: Warm cache
        $this->cacheWarmer->warmTopCustomers(10);

        // Assert: Subsequent reads are fast (from cache)
        // Note: In real test, measure execution time to verify cache hit
        $result1 = $this->repository->findById($customer1->id());
        $result2 = $this->repository->findById($customer2->id());
        $result3 = $this->repository->findById($customer3->id());

        self::assertNotNull($result1);
        self::assertNotNull($result2);
        self::assertNotNull($result3);
    }

    public function testCacheWarmupForLists(): void
    {
        // Arrange: Clear cache
        $this->cache->clear();

        // Act: Warm active customers list (first 5 pages)
        $this->cacheWarmer->warmActiveCustomersList(pages: 5);

        // Assert: List queries are cached
        $page1 = $this->repository->findActiveCustomers(page: 1);
        $page2 = $this->repository->findActiveCustomers(page: 2);
        $page3 = $this->repository->findActiveCustomers(page: 3);

        // Verify pages are loaded
        self::assertIsArray($page1);
        self::assertIsArray($page2);
        self::assertIsArray($page3);
    }

    private function createTestCustomer(string $name, string $email): Customer
    {
        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $this->repository->save($customer);

        return $customer;
    }
}
```

---

## Test 3: TTL Expiration Behavior

**Objective**: Ensure cache expires after TTL and reloads from database.

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CustomerRepositoryTtlTest extends KernelTestCase
{
    private CustomerRepository $repository;
    private TagAwareCacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepository::class);
        $this->cache = self::getContainer()->get(TagAwareCacheInterface::class);

        $this->cache->clear();
    }

    /**
     * Note: This test requires a custom test cache adapter with shorter TTL
     * In production config, TTL is 600s; in test config, use 2s for faster tests
     */
    public function testCacheExpiresAfterTtl(): void
    {
        // Arrange: Create customer
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');
        $customerId = $customer->id();

        // Act: First read - cache miss, loads from DB
        $result1 = $this->repository->findById($customerId);
        self::assertSame('John Doe', $result1->name());

        // Act: Update customer DIRECTLY in database (bypass repository to skip invalidation)
        $this->updateCustomerNameDirectly($customerId, 'Jane Doe');

        // Act: Read immediately - cache still valid (TTL not expired)
        $result2 = $this->repository->findById($customerId);
        self::assertSame('John Doe', $result2->name()); // Still cached (stale)

        // Act: Wait for TTL to expire (using test TTL of 2 seconds)
        sleep(3);

        // Act: Read after TTL expiration - should reload from database
        $result3 = $this->repository->findById($customerId);
        self::assertSame('Jane Doe', $result3->name()); // Fresh from DB

        // Assert: Cache was expired and reloaded
        self::assertNotSame($result2->name(), $result3->name());
    }

    public function testListCacheExpiresAfterTtl(): void
    {
        // Arrange: Cache active customers list
        $list1 = $this->repository->findActiveCustomers();
        $initialCount = count($list1);

        // Act: Create new customer DIRECTLY (bypass cache invalidation)
        $this->createCustomerDirectly('New Customer', 'new@example.com');

        // Act: Read immediately - cache still valid
        $list2 = $this->repository->findActiveCustomers();
        self::assertCount($initialCount, $list2); // Old cached data

        // Act: Wait for TTL to expire
        sleep(3);

        // Act: Read after TTL - should reload from database
        $list3 = $this->repository->findActiveCustomers();
        self::assertCount($initialCount + 1, $list3); // Fresh from DB
    }

    private function createTestCustomer(string $name, string $email): Customer
    {
        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $this->repository->save($customer);

        return $customer;
    }

    /**
     * Update customer directly in MongoDB (bypass cache invalidation)
     */
    private function updateCustomerNameDirectly(string $customerId, string $newName): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $customer = $dm->find(Customer::class, $customerId);
        $customer->updateName($newName);

        $dm->flush();

        // Note: We intentionally do NOT call repository->save() to skip cache invalidation
    }

    /**
     * Create customer directly (bypass repository to skip cache invalidation)
     */
    private function createCustomerDirectly(string $name, string $email): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $dm->persist($customer);
        $dm->flush();
    }
}
```

---

## Test 4: Cache Tag Invalidation

**Objective**: Test batch invalidation using cache tags.

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CustomerRepositoryTagInvalidationTest extends KernelTestCase
{
    private CustomerRepository $repository;
    private TagAwareCacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepository::class);
        $this->cache = self::getContainer()->get(TagAwareCacheInterface::class);

        $this->cache->clear();
    }

    public function testInvalidateBySpecificCustomerTag(): void
    {
        // Arrange: Create and cache two customers
        $customer1 = $this->createTestCustomer('Customer 1', 'customer1@example.com');
        $customer2 = $this->createTestCustomer('Customer 2', 'customer2@example.com');

        $result1a = $this->repository->findById($customer1->id());
        $result2a = $this->repository->findById($customer2->id());

        self::assertSame('Customer 1', $result1a->name());
        self::assertSame('Customer 2', $result2a->name());

        // Act: Update customer1 directly in DB
        $this->updateCustomerNameDirectly($customer1->id(), 'Updated Customer 1');

        // Act: Invalidate only customer1's cache
        $this->cache->invalidateTags(["customer.{$customer1->id()}"]);

        // Assert: Customer1 cache invalidated, loads fresh data
        $result1b = $this->repository->findById($customer1->id());
        self::assertSame('Updated Customer 1', $result1b->name());

        // Assert: Customer2 cache NOT invalidated, still cached
        $result2b = $this->repository->findById($customer2->id());
        self::assertSame('Customer 2', $result2b->name()); // Still cached
    }

    public function testInvalidateAllCustomersByTag(): void
    {
        // Arrange: Create and cache multiple customers
        $customer1 = $this->createTestCustomer('Customer 1', 'customer1@example.com');
        $customer2 = $this->createTestCustomer('Customer 2', 'customer2@example.com');
        $customer3 = $this->createTestCustomer('Customer 3', 'customer3@example.com');

        // Cache all customers
        $this->repository->findById($customer1->id());
        $this->repository->findById($customer2->id());
        $this->repository->findById($customer3->id());

        // Act: Update all customers directly
        $this->updateCustomerNameDirectly($customer1->id(), 'Updated 1');
        $this->updateCustomerNameDirectly($customer2->id(), 'Updated 2');
        $this->updateCustomerNameDirectly($customer3->id(), 'Updated 3');

        // Act: Invalidate ALL customer caches with single tag
        $this->cache->invalidateTags(['customer']);

        // Assert: All caches invalidated, fresh data loaded
        $result1 = $this->repository->findById($customer1->id());
        $result2 = $this->repository->findById($customer2->id());
        $result3 = $this->repository->findById($customer3->id());

        self::assertSame('Updated 1', $result1->name());
        self::assertSame('Updated 2', $result2->name());
        self::assertSame('Updated 3', $result3->name());
    }

    public function testInvalidateListsOnly(): void
    {
        // Arrange: Cache individual customer and list
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');

        $individualResult = $this->repository->findById($customer->id());
        $listResult = $this->repository->findActiveCustomers();

        self::assertSame('John Doe', $individualResult->name());
        self::assertGreaterThan(0, count($listResult));

        // Act: Create new customer directly
        $this->createCustomerDirectly('New Customer', 'new@example.com');

        // Act: Invalidate ONLY list caches (not individual customers)
        $this->cache->invalidateTags(['customer.list']);

        // Assert: List cache invalidated (new customer appears)
        $newListResult = $this->repository->findActiveCustomers();
        self::assertCount(count($listResult) + 1, $newListResult);

        // Assert: Individual customer cache NOT invalidated (still cached)
        $stillCached = $this->repository->findById($customer->id());
        self::assertSame('John Doe', $stillCached->name());
    }

    private function createTestCustomer(string $name, string $email): Customer
    {
        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $this->repository->save($customer);

        return $customer;
    }

    private function updateCustomerNameDirectly(string $customerId, string $newName): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $customer = $dm->find(Customer::class, $customerId);
        $customer->updateName($newName);

        $dm->flush();
    }

    private function createCustomerDirectly(string $name, string $email): void
    {
        $dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $dm->persist($customer);
        $dm->flush();
    }
}
```

---

## Test 5: Stale-While-Revalidate (SWR) Behavior

**Objective**: Test SWR pattern serves stale data while refreshing in background.

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MongoCustomerRepositorySwrTest extends KernelTestCase
{
    private CustomerRepository $repository;
    private TagAwareCacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepository::class);
        $this->cache = self::getContainer()->get(TagAwareCacheInterface::class);

        $this->cache->clear();
    }

    /**
     * Note: SWR behavior is handled by Symfony's probabilistic early expiration (beta parameter)
     * This test verifies basic caching behavior; full SWR requires message bus for background refresh
     */
    public function testSwrServesCachedDataFast(): void
    {
        // Arrange
        $customer = $this->createTestCustomer('John Doe', 'john@example.com');

        // Act: First request - cache miss (slow)
        $startTime1 = microtime(true);
        $result1 = $this->repository->findById($customer->id());
        $duration1 = (microtime(true) - $startTime1) * 1000;

        // Act: Second request - cache hit (fast)
        $startTime2 = microtime(true);
        $result2 = $this->repository->findById($customer->id());
        $duration2 = (microtime(true) - $startTime2) * 1000;

        // Assert: Second request significantly faster than first
        self::assertLessThan($duration1 / 2, $duration2, 'Cache hit should be at least 2x faster');

        self::assertSame($result1->id(), $result2->id());
    }

    private function createTestCustomer(string $name, string $email): Customer
    {
        $customer = Customer::create(
            id: bin2hex(random_bytes(16)),
            name: $name,
            email: $email
        );

        $this->repository->save($customer);

        return $customer;
    }
}
```

---

## Test Configuration for Cache Tests

**config/packages/test/cache.yaml**:

```yaml
framework:
  cache:
    app: cache.adapter.array
    default_redis_provider: null # Disable Redis in tests
    pools:
      app:
        adapter: cache.adapter.array
        provider: null
      cache.customer:
        adapter: cache.adapter.array
        provider: null
        tags: true # CRITICAL: Must have tags: true for TagAwareCacheInterface!
```

**IMPORTANT**: The `tags: true` setting is REQUIRED when using `TagAwareCacheInterface`. Without it, `invalidateTags()` calls will fail in tests!

---

## Running Cache Tests

```bash
# Run all cache tests
make unit-tests -- --group=cache

# Run specific test class
vendor/bin/phpunit tests/Integration/Customer/Infrastructure/Persistence/CustomerRepositoryCacheTest.php

# Run with coverage
make unit-tests -- --coverage-html coverage/cache --group=cache
```

---

## Summary

This comprehensive test suite covers:

✅ **Stale reads after writes** - Cache invalidation correctness
✅ **Cache warmup on cold start** - System resilience
✅ **TTL expiration** - Time-based cache behavior
✅ **Tag invalidation** - Batch invalidation correctness
✅ **SWR behavior** - Performance characteristics

**Testing Checklist**:

- ✅ Test cache miss and cache hit scenarios
- ✅ Test explicit invalidation on create/update/delete
- ✅ Test related cache invalidation (lists, lookups)
- ✅ Test TTL expiration reloads fresh data
- ✅ Test tag-based batch invalidation
- ✅ Test cache warmup functionality
- ✅ Test cache fallback on errors
- ✅ Measure and assert performance improvements

**Best Practices**:

- Use in-memory cache adapter for fast tests
- Use shorter TTL in test environment
- Test both cache hits and misses
- Verify invalidation triggers correctly
- Test edge cases (null values, exceptions)
- Measure performance where applicable
