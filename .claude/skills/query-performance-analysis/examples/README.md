# Query Performance Analysis - Examples

Practical examples for detecting and fixing query performance issues with MySQL/MariaDB.

## Available Examples

### 1. [N+1 Query Detection](n-plus-one-detection.md)

**When to use**: Endpoint makes many database queries

**Covers**:

- Detecting N+1 patterns in code
- Using Symfony Profiler to find repeated queries
- Fixing with eager loading (JOIN + addSelect)
- Fixing with batch loading
- Fixing with DQL subqueries

**Scenario**: GET /api/users returns users with their confirmation tokens, causing 101 queries (1 + 100)

---

### 2. [Slow Query Analysis](slow-query-analysis.md)

**When to use**: Query execution time is high

**Covers**:

- Using MySQL EXPLAIN command
- Interpreting EXPLAIN results
- Identifying full table scans vs index scans
- Understanding query execution plans
- Finding inefficient queries

**Scenario**: Search endpoint takes 3 seconds due to full table scan

---

## How to Use These Examples

### 1. Choose the Right Example

Match your problem to an example:

- **Too many queries** → N+1 Query Detection
- **Slow query execution** → Slow Query Analysis

### 2. Follow the Step-by-Step Guide

Each example provides:

1. **Problem description**: What's wrong
2. **Detection steps**: How to find the issue
3. **Analysis**: Understanding the problem
4. **Solution code**: How to fix it
5. **Verification**: Confirming the fix works
6. **Common questions**: FAQ

### 3. Adapt to Your Needs

Examples use user/token entities - adapt for:

- Your entity names
- Your query patterns
- Your performance thresholds
- Your table sizes

### 4. Validate Performance

After applying a fix:

```sql
-- Re-check query execution
EXPLAIN ANALYZE SELECT ...;
```

```bash
# Test endpoint
curl https://localhost/api/your-endpoint
```

Check Symfony Profiler for query count and timing.

## Quick Reference

| Problem            | Example             | Key Tool                  |
| ------------------ | ------------------- | ------------------------- |
| Many queries (N+1) | N+1 Detection       | Symfony Profiler          |
| Slow query         | Slow Query Analysis | EXPLAIN / EXPLAIN ANALYZE |

## Combining Examples

Some scenarios require multiple examples:

### Optimizing Slow Endpoint

1. **N+1 Detection** → Find and fix repeated queries
2. **Slow Query Analysis** → Find remaining slow queries
3. **Load Testing** (see [load-testing](../../load-testing/SKILL.md)) → Confirm performance

## Tips for Success

### 1. Start with N+1 Detection

Most performance issues are N+1 queries. Fix these first.

### 2. Use Symfony Profiler Liberally

Check profiler in development to catch issues early:

- Query count per request
- Query timing breakdown
- Duplicate query detection

### 3. EXPLAIN Every Query

Before adding an index, run EXPLAIN to see if it's actually needed.

### 4. Measure Before and After

Always measure performance before and after changes:

```php
$start = microtime(true);
$result = $repository->findSomething();
$duration = (microtime(true) - $start) * 1000;
echo "Duration: {$duration}ms\n";
```

### 5. Add Performance Tests

Prevent regressions with tests:

```php
public function testUserEndpointPerformance(): void
{
    $this->assertQueryCount('<10', 'Should use eager loading');
    $this->assertResponseTime(200, 'Should respond quickly');
}
```

## Performance Thresholds

Use these as guidelines:

| Operation                   | Target | Max Acceptable |
| --------------------------- | ------ | -------------- |
| Read single                 | <50ms  | 100ms          |
| Read collection (10 items)  | <100ms | 200ms          |
| Read collection (100 items) | <200ms | 500ms          |
| Write single                | <100ms | 300ms          |
| Write batch (10 items)      | <500ms | 1000ms         |
| Query count per endpoint    | <5     | 10             |

## Common Patterns Across Examples

All examples demonstrate:

- **Detection**: How to find the problem
- **Analysis**: Understanding why it's slow
- **Solution**: Code to fix it
- **Verification**: Proving it's fixed
- **Prevention**: Tests to avoid regression

## When Examples Don't Cover Your Case

If you don't find an exact match:

1. **Find the closest example** (similar query pattern)
2. **Review reference documentation**:
   - [MySQL Slow Query Guide](../reference/mysql-slow-query-guide.md)
   - [Index Strategies](../reference/index-strategies.md)
   - [Performance Thresholds](../reference/performance-thresholds.md)
3. **Follow general workflow** from main [SKILL.md](../SKILL.md)
4. **Use EXPLAIN** to understand query execution

## Need More Help?

- **Main skill documentation**: [SKILL.md](../SKILL.md)
- **Reference documentation**: [reference/](../reference/)
- **Related skills**:
  - [database-migrations](../../database-migrations/SKILL.md) - Index creation syntax
  - [load-testing](../../load-testing/SKILL.md) - Performance under load
  - [testing-workflow](../../testing-workflow/SKILL.md) - Performance tests
