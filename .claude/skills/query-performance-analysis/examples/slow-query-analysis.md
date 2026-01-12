# Slow Query Analysis with EXPLAIN

## Scenario

Search endpoint `/api/users?search=john` takes 3 seconds to respond. Need to identify why it's slow and fix it.

## Problem Description

```php
public function searchUsers(string $term): array
{
    return $this->createQueryBuilder('u')
        ->where('u.email LIKE :term')
        ->setParameter('term', "%{$term}%")
        ->getQuery()
        ->getResult();
}
```

**Symptom**: Takes 3+ seconds for 50,000 rows

---

## Step 1: Enable MySQL Slow Query Log

```bash
docker compose exec database mariadb -u root -proot db
```

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;  -- 100ms threshold
SET GLOBAL log_queries_not_using_indexes = 'ON';
```

---

## Step 2: Execute Query

```bash
curl "https://localhost/api/users?search=john"
```

---

## Step 3: Find Slow Queries

```sql
-- Check slow query log
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 5;
```

**Or use Symfony Profiler:**

Navigate to `https://localhost/_profiler`, select the request, and view the Doctrine panel.

---

## Step 4: Run EXPLAIN

```sql
EXPLAIN SELECT * FROM users WHERE email LIKE '%john%';
```

**Output**:

```
+----+-------------+-------+------+---------------+------+---------+------+-------+-------------+
| id | select_type | table | type | possible_keys | key  | key_len | ref  | rows  | Extra       |
+----+-------------+-------+------+---------------+------+---------+------+-------+-------------+
|  1 | SIMPLE      | users | ALL  | NULL          | NULL | NULL    | NULL | 50000 | Using where |
+----+-------------+-------+------+---------------+------+---------+------+-------+-------------+
```

### Red Flags 🚨

1. **`type: ALL`** - Full table scan (no index)
2. **`key: NULL`** - No index used
3. **`rows: 50000`** - Scanning entire table
4. **`possible_keys: NULL`** - No applicable indexes

---

## Step 5: Run EXPLAIN ANALYZE (MariaDB 10.1+/MySQL 8.0+)

```sql
EXPLAIN ANALYZE SELECT * FROM users WHERE email LIKE '%john%';
```

**Output**:

```
-> Filter: (users.email like '%john%')  (cost=5042 rows=5000) (actual time=2847..2901 rows=12 loops=1)
    -> Table scan on users  (cost=5042 rows=50000) (actual time=0.15..2634 rows=50000 loops=1)
```

**Analysis**:

- **Estimated rows: 50000** - Scanning everything
- **Actual rows returned: 12** - Only 12 matches
- **Actual time: 2847ms** - Very slow!

---

## Analysis

### Problem

LIKE query with leading wildcard (`%john%`) **cannot use indexes**. MySQL must scan every row.

### Why Indexes Don't Help Here

```sql
-- Index CAN help (prefix match)
WHERE email LIKE 'john%'

-- Index CANNOT help (leading wildcard)
WHERE email LIKE '%john%'
```

---

## Solutions

### Solution 1: Use Full-Text Index (Best for Search)

```sql
-- Add full-text index
ALTER TABLE users ADD FULLTEXT INDEX ft_email (email);
```

**Update query**:

```php
public function searchUsers(string $term): array
{
    return $this->createQueryBuilder('u')
        ->where('MATCH(u.email) AGAINST(:term IN BOOLEAN MODE)')
        ->setParameter('term', $term . '*')
        ->getQuery()
        ->getResult();
}
```

**Doctrine Migration**:

```php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE users ADD FULLTEXT INDEX ft_email (email)');
}
```

---

### Solution 2: Use Prefix Match (If Applicable)

```php
// If searching from beginning is acceptable
public function searchUsersByPrefix(string $term): array
{
    return $this->createQueryBuilder('u')
        ->where('u.email LIKE :term')
        ->setParameter('term', $term . '%')  // No leading wildcard
        ->getQuery()
        ->getResult();
}
```

**Add regular index**:

```xml
<!-- config/doctrine/User.orm.xml -->
<indexes>
    <index name="idx_email" columns="email"/>
</indexes>
```

---

### Solution 3: Computed Column for Email Domain

```sql
-- If searching by email domain is common
ALTER TABLE users ADD COLUMN email_domain VARCHAR(255)
    GENERATED ALWAYS AS (SUBSTRING_INDEX(email, '@', -1)) STORED;

CREATE INDEX idx_email_domain ON users (email_domain);
```

```php
public function searchByDomain(string $domain): array
{
    return $this->createQueryBuilder('u')
        ->where('u.emailDomain = :domain')
        ->setParameter('domain', $domain)
        ->getQuery()
        ->getResult();
}
```

---

## Verification

### Step 1: Run EXPLAIN Again

```sql
-- For full-text search
EXPLAIN SELECT * FROM users WHERE MATCH(email) AGAINST('john*' IN BOOLEAN MODE);
```

**Output After Fix**:

```
+----+-------------+-------+----------+---------------+----------+---------+------+------+-------------+
| id | select_type | table | type     | possible_keys | key      | key_len | ref  | rows | Extra       |
+----+-------------+-------+----------+---------------+----------+---------+------+------+-------------+
|  1 | SIMPLE      | users | fulltext | ft_email      | ft_email | 0       | NULL |    1 | Using where |
+----+-------------+-------+----------+---------------+----------+---------+------+------+-------------+
```

### Improvement

- **Before**: type=ALL, rows=50000, 2847ms
- **After**: type=fulltext, rows=1, 15ms
- **Result**: **189x faster!** ✅

---

## EXPLAIN Key Columns Reference

| Column          | Good Value                        | Bad Value                           | Meaning                    |
| --------------- | --------------------------------- | ----------------------------------- | -------------------------- |
| `type`          | `const`, `eq_ref`, `ref`, `range` | `ALL`                               | Access method              |
| `possible_keys` | Index names                       | `NULL`                              | Potentially usable indexes |
| `key`           | Index name                        | `NULL`                              | Actually used index        |
| `rows`          | Low number                        | High (>1000)                        | Estimated rows to examine  |
| `Extra`         | `Using index`                     | `Using filesort`, `Using temporary` | Additional info            |

### Type Values (Best to Worst)

| Type     | Description                       |
| -------- | --------------------------------- |
| `system` | Single row table                  |
| `const`  | Single row match (primary/unique) |
| `eq_ref` | One row per combination (JOIN)    |
| `ref`    | Multiple rows from index          |
| `range`  | Index range scan                  |
| `index`  | Full index scan                   |
| `ALL`    | Full table scan 🚨                |

---

## Common Slow Query Patterns

### Pattern 1: No Index (type: ALL)

```sql
-- Slow
SELECT * FROM users WHERE status = 'active';

-- type: ALL, rows: 50000
```

**Fix**: Add index on `status`

```sql
CREATE INDEX idx_status ON users (status);
```

---

### Pattern 2: Non-Selective Index

```sql
-- Has index but not selective
SELECT * FROM users WHERE status = 'active';
-- 49,000 of 50,000 are active!

-- type: ref, rows: 49000
```

**Fix**: Add composite index or use more specific query

---

### Pattern 3: Wrong Index Order (Composite Index)

```sql
-- Index: (status, created_at)
-- Query:
SELECT * FROM users WHERE created_at > '2024-01-01';

-- type: ALL (can't use composite index!)
```

**Fix**: Create separate index on `created_at` or use both fields

```sql
SELECT * FROM users WHERE status = 'active' AND created_at > '2024-01-01';
-- Now composite index works!
```

---

### Pattern 4: LIKE with Leading Wildcard

```sql
-- Can't use index
SELECT * FROM users WHERE email LIKE '%john%';

-- type: ALL
```

**Fix**: Use full-text index or prefix match

---

### Pattern 5: Function on Indexed Column

```sql
-- Can't use index
SELECT * FROM users WHERE LOWER(email) = 'john@example.com';

-- type: ALL
```

**Fix**: Store normalized data or use generated column

```sql
ALTER TABLE users ADD COLUMN email_lower VARCHAR(255)
    GENERATED ALWAYS AS (LOWER(email)) STORED;
CREATE INDEX idx_email_lower ON users (email_lower);
```

---

### Pattern 6: ORDER BY without Index

```sql
-- Sort without index
SELECT * FROM users ORDER BY created_at DESC LIMIT 100;

-- Extra: Using filesort 🚨
```

**Fix**: Add index on sort column

```sql
CREATE INDEX idx_created_at ON users (created_at DESC);
```

---

## Performance Testing

```php
final class UserSearchPerformanceTest extends ApiTestCase
{
    public function testSearchUsersPerformance(): void
    {
        // Arrange: Create realistic dataset
        for ($i = 0; $i < 1000; $i++) {
            $this->createUser(['email' => "user{$i}@example.com"]);
        }

        // Act: Measure search performance
        $start = microtime(true);
        $response = $this->client->request('GET', '/api/users', [
            'query' => ['search' => 'user']
        ]);
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Should be fast
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(100, $duration,
            "Search too slow: {$duration}ms"
        );
    }
}
```

---

## Common Questions

### Q: When should I run EXPLAIN?

**A**: Always run EXPLAIN when:

- Query takes >100ms
- Adding a new query pattern
- Suspecting index isn't being used
- Optimizing existing queries

### Q: What if index exists but type=ALL?

**A**: Index isn't applicable for the query. Check:

- Query uses indexed columns correctly
- No functions on indexed columns
- LIKE pattern allows index usage
- Composite index field order matches query

### Q: Can I run EXPLAIN in production?

**A**: Yes, EXPLAIN doesn't execute the query. Use:

```sql
EXPLAIN SELECT ...;  -- Safe, doesn't execute
EXPLAIN ANALYZE SELECT ...;  -- Executes query, use carefully
```

### Q: How do I find ALL slow queries?

**A**: Use slow query log:

```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;
```

---

## Next Steps

1. **Check for N+1 queries**: [n-plus-one-detection.md](n-plus-one-detection.md)
2. **Add missing indexes**: [../reference/index-strategies.md](../reference/index-strategies.md)
3. **Review performance thresholds**: [../reference/performance-thresholds.md](../reference/performance-thresholds.md)
