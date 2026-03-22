# Performance Thresholds

Acceptable performance limits for database operations.

## Response Time Thresholds

### API Endpoints

| Operation Type             | Target | Max Acceptable | Critical |
| -------------------------- | ------ | -------------- | -------- |
| GET single resource        | <50ms  | 100ms          | >200ms   |
| GET collection (10 items)  | <100ms | 200ms          | >500ms   |
| GET collection (100 items) | <200ms | 500ms          | >1000ms  |
| POST (create)              | <100ms | 300ms          | >500ms   |
| PATCH/PUT (update)         | <100ms | 300ms          | >500ms   |
| DELETE                     | <50ms  | 200ms          | >400ms   |
| Search/Filter              | <150ms | 300ms          | >600ms   |

### Database Queries

| Query Type                   | Target | Max Acceptable | Critical   |
| ---------------------------- | ------ | -------------- | ---------- |
| Find by ID (primary key)     | <5ms   | 20ms           | >50ms      |
| Find by indexed field        | <10ms  | 50ms           | >100ms     |
| Find by non-indexed field    | N/A    | N/A            | Add index! |
| Full table scan (<1000 rows) | <50ms  | 100ms          | >200ms     |
| Full table scan (>1000 rows) | N/A    | N/A            | Add index! |
| JOIN (2 tables)              | <50ms  | 100ms          | >200ms     |
| JOIN (3+ tables)             | <100ms | 200ms          | >500ms     |
| Aggregation (simple)         | <100ms | 200ms          | >500ms     |
| Aggregation (complex)        | <300ms | 500ms          | >1000ms    |

## Query Count Thresholds

| Operation           | Target | Max Acceptable | Critical |
| ------------------- | ------ | -------------- | -------- |
| GET single resource | 1-2    | 3              | >5       |
| GET collection      | 1-3    | 5              | >10      |
| POST (create)       | 1-2    | 4              | >8       |
| Complex operation   | 3-5    | 10             | >15      |

**Note**: More than 10 queries usually indicates N+1 problem!

## Rows Examined Ratio

**Formula**: `Rows_examined / Rows_sent`

| Ratio   | Performance     | Action                           |
| ------- | --------------- | -------------------------------- |
| 1:1     | ✅ Excellent    | Perfect index usage              |
| 2:1     | ✅ Good         | Acceptable                       |
| 10:1    | ⚠️ Poor         | Review index selectivity         |
| 100:1   | 🚨 Critical     | Add better index or refine query |
| 1000:1+ | 🚨 Unacceptable | Immediate action required        |

**Example**:

```text
Rows_sent: 10
Rows_examined: 100
Ratio: 100/10 = 10:1 (Poor - review index)
```

## Index Usage Metrics

### Query Analysis

**Target**: >95% of queries should use indexes

```sql
-- Check if query uses index
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
-- key: idx_email (good)
-- key: NULL (bad - add index)
```

### Ideal Index Characteristics

| Metric            | Target             | Notes                                 |
| ----------------- | ------------------ | ------------------------------------- |
| Index selectivity | >10% unique values | High cardinality = better selectivity |
| Index size        | <30% of table size | Compact indexes are efficient         |
| Index usage       | Used by queries    | Unused indexes should be dropped      |

## Table Size Guidelines

| Table Size        | Max Query Time | Strategy                                |
| ----------------- | -------------- | --------------------------------------- |
| <1,000 rows       | 50ms           | Indexes optional for simple queries     |
| 1,000-10,000 rows | 100ms          | Index frequently queried fields         |
| 10,000-100,000    | 200ms          | Composite indexes, careful query design |
| 100,000-1M rows   | 300ms          | Aggressive indexing, query optimization |
| >1M rows          | 500ms          | Partitioning, caching, read replicas    |

## MySQL/MariaDB-Specific Thresholds

### Slow Query Log Thresholds

```sql
-- Development (all operations for thorough analysis)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;  -- 100ms

-- Staging (moderate threshold)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.2;  -- 200ms

-- Production (slow queries only)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.5;  -- 500ms
```

### Connection Pool

| Metric               | Target | Max  |
| -------------------- | ------ | ---- |
| Active connections   | <50    | 100  |
| Connection wait time | <10ms  | 50ms |

### InnoDB Buffer Pool

| Metric           | Warning  | Critical |
| ---------------- | -------- | -------- |
| Buffer pool hit  | <95%     | <90%     |
| Buffer pool size | <50% RAM | <25% RAM |

## Performance Degradation Triggers

Investigate when:

- Query time increases >50% from baseline
- Query count doubles for same operation
- Rows_examined ratio exceeds 10:1
- Any query takes >1 second
- Full table scan on table >1000 rows
- EXPLAIN shows `type: ALL` on indexed columns

## Load Testing Thresholds

### Concurrent Users

| Users | Target p95 | Max Acceptable |
| ----- | ---------- | -------------- |
| 10    | <100ms     | 200ms          |
| 50    | <200ms     | 400ms          |
| 100   | <300ms     | 600ms          |
| 500   | <500ms     | 1000ms         |
| 1000  | <800ms     | 1500ms         |

### Throughput

| Operation         | Target RPS | Acceptable |
| ----------------- | ---------- | ---------- |
| Read single       | 1000+      | 500+       |
| Read collection   | 500+       | 200+       |
| Write single      | 500+       | 200+       |
| Complex operation | 100+       | 50+        |

## Monitoring and Alerting

### Alert Thresholds

**Warning** (investigate):

- Query time >200ms
- Query count >10 per request
- Rows_examined ratio >10:1
- Full table scans on tables >10,000 rows

**Critical** (immediate action):

- Query time >1000ms
- Query count >20 per request
- Rows_examined ratio >100:1
- Any full table scan on tables >100,000 rows
- Lock wait timeout

## Environment-Specific Thresholds

### Development

- More lenient thresholds
- Enable aggressive slow query logging (100ms)
- Log all queries for analysis
- Enable Symfony Profiler

### Staging

- Production-like thresholds
- Moderate slow query logging (200ms)
- Performance testing focus
- Load testing validation

### Production

- Strict thresholds
- Conservative slow query logging (500ms)
- Alerting on degradation
- Continuous monitoring

## How to Use These Thresholds

### In Tests

```php
public function testUserEndpointPerformance(): void
{
    $start = microtime(true);
    $response = $this->client->request('GET', '/api/users');
    $duration = (microtime(true) - $start) * 1000;

    // Use threshold from table: GET collection (100 items) = 200ms target
    $this->assertLessThan(200, $duration);
}

public function testQueryCount(): void
{
    $this->client->enableProfiler();
    $this->client->request('GET', '/api/users');

    $collector = $this->client->getProfile()->getCollector('db');
    $queryCount = $collector->getQueryCount();

    // Use threshold from table: GET collection = <5 target
    $this->assertLessThan(5, $queryCount);
}
```

### In Monitoring

```yaml
# Prometheus alert rules
- alert: SlowAPIEndpoint
  expr: api_request_duration_ms > 200
  for: 5m
  annotations:
    summary: 'API endpoint exceeds 200ms threshold'

- alert: HighQueryCount
  expr: db_queries_per_request > 10
  for: 5m
  annotations:
    summary: 'Possible N+1 query issue detected'
```

### In Code Reviews

```php
// ❌ REJECT: 45 queries for single endpoint (threshold: <10)
// ❌ REJECT: 1.2s response time (threshold: <500ms)
// ❌ REJECT: EXPLAIN shows type=ALL on 50k row table
// ✅ APPROVE: 3 queries, 85ms response time, type=ref
```

## Exceptions

Some operations may exceed thresholds justifiably:

- **Reporting endpoints**: May take >1s for complex aggregations
- **Export operations**: Can take minutes for large datasets
- **Batch operations**: Expected to be slower
- **First-time cache population**: One-time performance hit
- **Complex search**: May require multiple queries

**Always document exceptions** in code comments!

```php
/**
 * NOTE: This report aggregates 1M+ rows.
 * Expected execution time: 5-10 seconds.
 * Runs asynchronously via job queue.
 */
public function generateAnnualReport(): void
{
    // ...
}
```

## References

- **[MySQL Query Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)**
- **[MariaDB Query Optimization](https://mariadb.com/kb/en/query-optimization/)**
- **[Web Performance Budgets](https://web.dev/performance-budgets-101/)**
