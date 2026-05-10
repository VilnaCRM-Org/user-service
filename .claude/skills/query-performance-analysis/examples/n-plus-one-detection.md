# N+1 Query Detection and Fix

## Scenario

You've implemented GET `/api/users` that returns users with their confirmation tokens. Users complain it's slow. Symfony Profiler shows 101 database queries!

## Problem Description

```php
// Current implementation - State Provider
public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
{
    $users = $this->userRepository->findAll();  // 1 query

    foreach ($users as $user) {
        // Each access to lazy-loaded relation triggers a query!
        $token = $user->getConfirmationToken();  // N queries!
    }

    return $users;
}
```

**Result**: 1 + 100 = **101 queries** for 100 users!

---

## Detection Steps

### Step 1: Enable Symfony Profiler

```bash
# Run the endpoint
curl https://localhost/api/users

# Check profiler
open https://localhost/_profiler

# Look at "Doctrine" panel
# Query count: 101 queries 🚨
```

### Step 2: Identify Repeated Queries

In Symfony Profiler, look for:

- **Duplicate queries section** - shows identical queries
- **Query timeline** - shows queries executed in sequence
- **Similar queries** - queries with same structure, different parameters

### Step 3: Analyze Query Pattern

Profiler will show:

```sql
-- Query 1: Get all users
SELECT * FROM users;

-- Query 2-101: Get token for each user (N times!)
SELECT * FROM confirmation_tokens WHERE user_id = ?;
SELECT * FROM confirmation_tokens WHERE user_id = ?;
SELECT * FROM confirmation_tokens WHERE user_id = ?;
-- ... repeated 100 times
```

**Analysis**: Same query executed 100 times = **N+1 problem!**

---

## Solutions

### Solution 1: Eager Loading with QueryBuilder (Recommended)

```php
// ✅ FIXED: Use JOIN + addSelect to eager load
public function findAllWithTokens(): array
{
    return $this->createQueryBuilder('u')
        ->leftJoin('u.confirmationToken', 't')
        ->addSelect('t')  // Eager load tokens!
        ->getQuery()
        ->getResult();
}
```

**Result**: **1 query total** (with JOIN)

---

### Solution 2: Batch Loading with IN Clause

```php
// ✅ ALTERNATIVE: Load all tokens in one query
public function findAllWithTokensBatch(): array
{
    // 1. Get all users
    $users = $this->createQueryBuilder('u')
        ->getQuery()
        ->getResult();  // 1 query

    // 2. Extract all user IDs
    $userIds = array_map(
        fn(User $u) => $u->getId(),
        $users
    );

    // 3. Load all tokens in one query
    $tokens = $this->tokenRepository
        ->createQueryBuilder('t')
        ->where('t.user IN (:userIds)')
        ->setParameter('userIds', $userIds)
        ->getQuery()
        ->getResult();  // 1 query

    // 4. Doctrine will hydrate relationships from identity map
    return $users;
}
```

**Result**: **2 queries total**

---

### Solution 3: DQL with FETCH JOIN

```php
// ✅ ALTERNATIVE: DQL fetch join
public function findAllWithTokensDQL(): array
{
    $dql = 'SELECT u, t
            FROM App\User\Domain\Entity\User u
            LEFT JOIN u.confirmationToken t';

    return $this->getEntityManager()
        ->createQuery($dql)
        ->getResult();
}
```

**Result**: **1 query total**

---

### Solution 4: API Platform Eager Loading Configuration

```yaml
# config/api_platform/resources/user.yaml
App\User\Domain\Entity\User:
  operations:
    get_collection:
      normalizationContext:
        groups: ['user:read']
      # Configure eager loading
      stateOptions:
        entityClass: App\User\Domain\Entity\User
        handleLinks: false
```

```php
// Custom State Provider with eager loading
final readonly class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        return $this->userRepository->findAllWithTokens();
    }
}
```

---

## Verification

### Step 1: Run Endpoint Again

```bash
curl https://localhost/api/users
```

### Step 2: Check Symfony Profiler

Navigate to `https://localhost/_profiler` and check the Doctrine panel.

**Before fix**:

```
Total Queries: 101
Time: 450ms
```

**After fix (eager loading)**:

```
Total Queries: 1
Time: 25ms
```

**Improvement**: 101x fewer queries, 18x faster! ✅

---

## Add Performance Test

```php
// tests/Performance/UserEndpointTest.php

final class UserEndpointTest extends ApiTestCase
{
    public function testGetUsersHasNoN1Queries(): void
    {
        // Arrange: Create 50 users with tokens
        for ($i = 0; $i < 50; $i++) {
            $this->createUserWithToken();
        }

        // Act: Enable query counter
        $this->enableProfiler();

        $response = $this->client->request('GET', '/api/users');

        // Assert: Should have minimal queries (not N+1)
        $collector = $this->client->getProfile()->getCollector('db');
        $queryCount = $collector->getQueryCount();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(10, $queryCount,
            "N+1 query detected! Expected <10 queries, got {$queryCount}"
        );
    }

    public function testGetUsersPerformance(): void
    {
        // Arrange: Create realistic data set
        for ($i = 0; $i < 100; $i++) {
            $this->createUser();
        }

        // Act: Measure response time
        $start = microtime(true);
        $response = $this->client->request('GET', '/api/users');
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Should respond quickly
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(200, $duration,
            "GET /api/users too slow: {$duration}ms"
        );
    }
}
```

---

## Common Questions

### Q: How do I know if I have an N+1 problem?

**A**: Look for these signs:

- Query count grows with data size (100 items = 100+ queries)
- Same query executed many times with different parameters
- Queries inside `foreach` loops in your code
- Symfony Profiler shows high query count for simple operations

### Q: Which solution should I use?

**A**:

- **Eager loading with JOIN (Solution 1)**: Simplest, single query
- **Batch loading (Solution 2)**: More control, useful for complex scenarios
- **DQL fetch join (Solution 3)**: When you need custom DQL
- **API Platform provider (Solution 4)**: Cleanest for API Platform endpoints

Choose eager loading with JOIN for most cases.

### Q: What if I can't use eager loading?

**A**: Use batch loading (Solution 2). Always better than N+1 queries.

### Q: How do I test for N+1 in CI?

**A**: Add query count assertions to your tests (see "Add Performance Test" section above).

### Q: What's an acceptable query count?

**A**: Target <5 queries per endpoint, max 10. Never 100+!

### Q: Does FETCH JOIN work with pagination?

**A**: Be careful - FETCH JOIN with LIMIT can cause issues. Use batch loading for paginated collections or configure separate count query.

---

## Doctrine Best Practices for Preventing N+1

### 1. Configure Default Fetch Mode

```xml
<!-- config/doctrine/User.orm.xml -->
<entity name="App\User\Domain\Entity\User">
    <one-to-one field="confirmationToken" target-entity="ConfirmationToken" fetch="EAGER"/>
</entity>
```

**Note**: Use `fetch="EAGER"` sparingly - only when relation is always needed.

### 2. Use Query Hints

```php
$query = $this->createQueryBuilder('u')
    ->getQuery()
    ->setFetchMode(User::class, 'confirmationToken', ClassMetadata::FETCH_EAGER);
```

### 3. Avoid Lazy Loading in Serialization

Configure serialization groups to prevent lazy loading during JSON encoding:

```php
#[Groups(['user:read'])]
private function getTokenValue(): ?string
{
    // This would trigger lazy loading if not eagerly loaded!
    return $this->confirmationToken?->getToken();
}
```

---

## Next Steps

1. **Run EXPLAIN on remaining queries**: See [slow-query-analysis.md](slow-query-analysis.md)
2. **Add missing indexes**: Review [../reference/index-strategies.md](../reference/index-strategies.md)
3. **Add load tests**: Use [load-testing skill](../../load-testing/SKILL.md)
4. **Monitor in production**: Set up query monitoring

---

## Prevention Checklist

After fixing N+1:

- [ ] Symfony Profiler shows expected query count (<10)
- [ ] Performance test added to prevent regression
- [ ] Code review to check for similar patterns elsewhere
- [ ] Repository methods use eager loading by default
- [ ] Team informed about N+1 anti-pattern
