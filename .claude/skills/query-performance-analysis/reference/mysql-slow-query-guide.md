# MySQL/MariaDB Slow Query Log Guide

## Overview

The slow query log records all queries that exceed a specified execution time. Essential for detecting performance issues in production and development.

## Configuration Levels

| Setting               | Development      | Staging          | Production       |
| --------------------- | ---------------- | ---------------- | ---------------- |
| slow_query_log        | ON               | ON               | ON               |
| long_query_time       | 0.1 (100ms)      | 0.2 (200ms)      | 0.5 (500ms)      |
| log_queries_not_using_indexes | ON       | ON               | OFF              |

## Enable Slow Query Log

### Development (All Slow Queries)

```bash
docker compose exec database mariadb -u root -proot db
```

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;  -- 100ms
SET GLOBAL log_queries_not_using_indexes = 'ON';

-- Verify settings
SHOW VARIABLES LIKE 'slow_query%';
SHOW VARIABLES LIKE 'long_query_time';
```

### Production (Conservative Settings)

```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.5;  -- 500ms
SET GLOBAL log_queries_not_using_indexes = 'OFF';
```

### Check Status

```sql
SHOW VARIABLES LIKE 'slow_query_log';
SHOW VARIABLES LIKE 'long_query_time';
SHOW VARIABLES LIKE 'slow_query_log_file';
```

## Viewing Slow Queries

### Method 1: Query mysql.slow_log Table

```sql
-- View recent slow queries
SELECT 
    start_time,
    user_host,
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log 
ORDER BY start_time DESC 
LIMIT 10;
```

### Method 2: Read Log File

```bash
# Find log file location
docker compose exec database mariadb -u root -proot -e "SHOW VARIABLES LIKE 'slow_query_log_file';"

# View log file
docker compose exec database tail -100 /var/lib/mysql/slow-query.log
```

### Method 3: Use mysqldumpslow

```bash
# Summarize slow queries
docker compose exec database mysqldumpslow /var/lib/mysql/slow-query.log
```

## Log Entry Format

```
# Time: 2024-01-15T10:23:45.123456Z
# User@Host: root[root] @ localhost []
# Query_time: 2.847123  Lock_time: 0.000123 Rows_sent: 12  Rows_examined: 50000
SET timestamp=1705311825;
SELECT * FROM users WHERE email LIKE '%john%';
```

### Key Fields

| Field         | Description                        | Concern Threshold |
| ------------- | ---------------------------------- | ----------------- |
| Query_time    | Total execution time (seconds)     | >0.5s             |
| Lock_time     | Time waiting for locks             | >0.1s             |
| Rows_sent     | Rows returned to client            | Compare to examined |
| Rows_examined | Rows scanned by query              | >>Rows_sent       |

## Common Analysis Queries

### Find Slowest Queries

```sql
SELECT 
    sql_text,
    query_time,
    rows_examined,
    rows_sent
FROM mysql.slow_log 
ORDER BY query_time DESC 
LIMIT 10;
```

### Find Queries Examining Many Rows

```sql
SELECT 
    sql_text,
    rows_examined,
    rows_sent,
    rows_examined / GREATEST(rows_sent, 1) AS ratio
FROM mysql.slow_log 
WHERE rows_examined > 1000
ORDER BY rows_examined DESC 
LIMIT 10;
```

### Find Repeated Slow Queries

```sql
SELECT 
    LEFT(sql_text, 100) AS query_pattern,
    COUNT(*) AS occurrences,
    AVG(query_time) AS avg_time,
    MAX(query_time) AS max_time
FROM mysql.slow_log 
GROUP BY LEFT(sql_text, 100)
HAVING COUNT(*) > 5
ORDER BY occurrences DESC;
```

### Find Queries Not Using Indexes

```sql
SELECT 
    sql_text,
    query_time,
    rows_examined
FROM mysql.slow_log 
WHERE rows_examined > 100 
  AND rows_sent < 10
ORDER BY query_time DESC;
```

## Symfony Profiler Integration

### Enable Profiler (Development)

```yaml
# config/packages/dev/web_profiler.yaml
web_profiler:
    toolbar: true
    intercept_redirects: false

framework:
    profiler: { only_exceptions: false }
```

### View Query Information

1. Make request to endpoint
2. Click profiler toolbar at bottom
3. Select "Doctrine" panel
4. Review:
   - Total queries
   - Duplicate queries (N+1 detection)
   - Query timing
   - Individual query details

### Programmatic Access

```php
// In tests
public function testQueryCount(): void
{
    $this->client->enableProfiler();
    $this->client->request('GET', '/api/users');
    
    $profile = $this->client->getProfile();
    $collector = $profile->getCollector('db');
    
    $queryCount = $collector->getQueryCount();
    $this->assertLessThan(10, $queryCount);
}
```

## Managing Log Data

### Clear Slow Query Log

```sql
-- Truncate the log table
TRUNCATE mysql.slow_log;
```

### Rotate Log Files

```bash
# Flush logs to create new file
docker compose exec database mysqladmin -u root -proot flush-logs
```

### Limit Log Size

```sql
-- Set maximum log file size (requires restart)
-- In my.cnf:
-- max_binlog_size = 100M
```

## Integration with PHP

### Query Timing Helper

```php
final class QueryTimer
{
    public static function measure(callable $query): array
    {
        $start = microtime(true);
        $result = $query();
        $duration = (microtime(true) - $start) * 1000;

        return [
            'result' => $result,
            'duration_ms' => $duration,
        ];
    }
}

// Usage
$data = QueryTimer::measure(fn() => $repository->findByEmail($email));
if ($data['duration_ms'] > 100) {
    $logger->warning('Slow query', ['duration' => $data['duration_ms']]);
}
```

### EXPLAIN from PHP

```php
public function explainQuery(string $sql, array $params = []): array
{
    $conn = $this->entityManager->getConnection();
    return $conn->executeQuery('EXPLAIN ' . $sql, $params)->fetchAllAssociative();
}
```

## Best Practices

### DO ✅

- Enable slow query log in development
- Use Symfony Profiler for detailed analysis
- Set appropriate thresholds per environment
- Monitor Rows_examined vs Rows_sent ratio
- Clear logs regularly to manage size
- Include query analysis in code reviews

### DON'T ❌

- Leave very low threshold in production (<200ms)
- Enable log_queries_not_using_indexes in production
- Let log files grow unbounded
- Ignore repeated slow queries
- Skip EXPLAIN analysis before adding indexes

## Profiling Workflow

### Development Workflow

1. **Enable slow query log** (long_query_time: 0.1)
2. **Enable Symfony Profiler**
3. **Run endpoint/feature**
4. **Check profiler for query count**
5. **Check slow log for timing**
6. **Run EXPLAIN on slow queries**
7. **Fix issues** (N+1, missing indexes)
8. **Re-test and verify**

### Production Monitoring

1. **Enable slow query log** (long_query_time: 0.5)
2. **Export logs** to monitoring system
3. **Alert on slow queries**
4. **Investigate patterns**
5. **Fix in development**
6. **Deploy fixes**

## Common Patterns to Look For

### 🚨 Red Flag #1: High Rows Examined

```
Rows_examined: 50000
Rows_sent: 10
Ratio: 5000:1
→ Missing or ineffective index!
```

### 🚨 Red Flag #2: Lock Time

```
Lock_time: 2.5
Query_time: 3.0
→ Blocking/deadlock issues!
```

### 🚨 Red Flag #3: Repeated Patterns

```
Same query 100 times in 1 second
→ N+1 problem!
```

### 🚨 Red Flag #4: Simple Query Slow

```
SELECT * FROM users WHERE id = 123
Query_time: 1.5
→ Table locks, connection issues, or server overload
```

## Troubleshooting

**Issue**: Slow query log not capturing queries

**Solution**:

```sql
-- Verify log is enabled
SHOW VARIABLES LIKE 'slow_query_log';

-- Check threshold
SHOW VARIABLES LIKE 'long_query_time';

-- Ensure threshold is low enough
SET GLOBAL long_query_time = 0.1;
```

---

**Issue**: Log file too large

**Solution**:

```sql
-- Truncate table
TRUNCATE mysql.slow_log;

-- Or rotate logs
FLUSH SLOW LOGS;
```

---

**Issue**: Can't find slow query log file

**Solution**:

```sql
-- Check file location
SHOW VARIABLES LIKE 'slow_query_log_file';

-- Check if logging to table or file
SHOW VARIABLES LIKE 'log_output';
-- Values: FILE, TABLE, or FILE,TABLE
```

---

**Issue**: Symfony Profiler not showing queries

**Solution**:

```yaml
# Ensure profiler is enabled for API requests
# config/packages/dev/web_profiler.yaml
framework:
    profiler:
        only_exceptions: false
        collect: true
```

## External Resources

- **[MySQL Slow Query Log](https://dev.mysql.com/doc/refman/8.0/en/slow-query-log.html)**
- **[MariaDB Slow Query Log](https://mariadb.com/kb/en/slow-query-log/)**
- **[Symfony Profiler](https://symfony.com/doc/current/profiler.html)**
- **[Doctrine Debug Stack](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#sql-query-logging)**
