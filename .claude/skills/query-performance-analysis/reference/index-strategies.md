# Index Selection Strategies

Quick guide for choosing the right index type and structure for MySQL/MariaDB.

## When to Add an Index

Add an index when:

- Query filters on a field (`WHERE status = 'active'`)
- Query sorts on a field (`ORDER BY created_at DESC`)
- Query uses both filter and sort on same fields
- EXPLAIN shows `type: ALL` (full table scan)
- Query execution time >100ms

**Don't index**:

- Fields that are rarely queried
- Fields with very low cardinality (e.g., boolean with only 2 values on large tables)
- Tables with <1000 rows (usually fast enough without indexes)
- Write-heavy tables where read performance isn't critical

---

## Index Type Selection

### Single Column Index

**When**: Queries filter/sort on ONE field

```xml
<indexes>
    <index name="idx_email" columns="email"/>
</indexes>
```

**Migration**:

```php
$this->addSql('CREATE INDEX idx_email ON users (email)');
```

**Use cases**:

- `WHERE email = ?`
- `ORDER BY created_at DESC`
- Unique constraints

---

### Composite Index

**When**: Queries filter/sort on MULTIPLE fields

```xml
<indexes>
    <index name="idx_status_type_created" columns="status,type,created_at"/>
</indexes>
```

**Use cases**:

- `WHERE status = ? AND type = ?`
- `WHERE status = ? ORDER BY created_at DESC`

**IMPORTANT**: Field order matters!

- Leftmost prefix rule applies
- Equality filters before range filters
- Sort fields last

---

### Unique Index

**When**: Field must be unique (like email, username)

```xml
<unique-constraints>
    <unique-constraint name="uniq_email" columns="email"/>
</unique-constraints>
```

**Use cases**:

- Enforcing uniqueness at database level
- Primary keys
- Natural keys (email, username)

---

### Full-Text Index

**When**: Full-text search on string fields

```sql
ALTER TABLE users ADD FULLTEXT INDEX ft_email_name (email, name);
```

**Use cases**:

- Search functionality
- `MATCH() AGAINST()` queries
- Multiple string fields

**Limitations**:

- Only works with `MATCH() AGAINST()` syntax
- Not for exact matches
- InnoDB full-text has minimum word length (default 3)

---

### Covering Index

**When**: Query can be answered from index alone

```sql
CREATE INDEX idx_covering ON users (status, email, name);
```

**Query**:

```sql
SELECT email, name FROM users WHERE status = 'active';
-- Extra: Using index (no table access!)
```

**Use cases**:

- High-frequency read queries
- Queries returning few columns

---

## Composite Index Field Order

**Rule**: Equality → Sort → Range

### Example Query

```sql
SELECT * FROM users
WHERE status = 'active'      -- Equality
  AND type = 'premium'       -- Equality
ORDER BY created_at DESC;    -- Sort
```

### Optimal Index

```xml
<index name="idx_status_type_created" columns="status,type,created_at"/>
```

### Why This Order?

1. **Equality fields** narrow down results fastest
2. **Sort fields** can use index for sorting (avoids filesort)
3. **Range fields** stop further index usage for filtering

---

## Leftmost Prefix Rule

A composite index can be used for queries on its **leftmost prefixes**.

**Index**: `(status, type, created_at)`

**Can be used for**:

- ✅ `WHERE status = 'active'`
- ✅ `WHERE status = 'active' AND type = 'premium'`
- ✅ `WHERE status = 'active' AND type = 'premium' ORDER BY created_at`
- ✅ `WHERE status = 'active' ORDER BY type` (for sorting on second column)

**Cannot be used for**:

- ❌ `WHERE type = 'premium'` (skips first field)
- ❌ `WHERE created_at > '2024-01-01'` (skips first two fields)
- ❌ `ORDER BY type, created_at` (skips first field)

---

## Common Patterns

### Pattern 1: Status Filter + Date Sort

**Query**: Active items, newest first

```sql
SELECT * FROM users WHERE status = 'active' ORDER BY created_at DESC;
```

**Index**:

```xml
<index name="idx_status_created" columns="status,created_at"/>
```

---

### Pattern 2: Multiple Equality Filters

**Query**: Filter by status and type

```sql
SELECT * FROM users WHERE status = 'active' AND type = 'premium';
```

**Index**:

```xml
<index name="idx_status_type" columns="status,type"/>
```

---

### Pattern 3: Range Query + Sort

**Query**: Recent users from last week

```sql
SELECT * FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
ORDER BY created_at DESC;
```

**Index**:

```xml
<index name="idx_created_at" columns="created_at"/>
```

---

### Pattern 4: Unique Lookup

**Query**: Find by email

```sql
SELECT * FROM users WHERE email = 'user@example.com';
```

**Index**:

```xml
<unique-constraints>
    <unique-constraint name="uniq_email" columns="email"/>
</unique-constraints>
```

---

### Pattern 5: Foreign Key Lookup

**Query**: Find tokens by user

```sql
SELECT * FROM confirmation_tokens WHERE user_id = ?;
```

**Index**:

```xml
<index name="idx_user_id" columns="user_id"/>
```

**Note**: MySQL/InnoDB automatically creates indexes for foreign keys, but explicit index gives you control over naming.

---

### Pattern 6: Cursor Pagination

**Query**: Paginate with cursor

```sql
SELECT * FROM users WHERE id > ? ORDER BY id LIMIT 20;
```

**Index**: Primary key (automatic)

For filtered pagination:

```sql
SELECT * FROM users WHERE status = 'active' AND id > ? ORDER BY id LIMIT 20;
```

**Index**:

```xml
<index name="idx_status_id" columns="status,id"/>
```

---

## Decision Flowchart

```
Does query filter on ONE field?
├─ Yes → Single column index
└─ No
    ├─ Does query filter on MULTIPLE fields?
    │   └─ Yes → Composite index (equality → sort → range)
    └─ Does query need full-text search?
        └─ Yes → Full-text index
```

---

## Performance Considerations

### Index Size

- Each index consumes disk space and RAM
- Target: 3-5 indexes per table max
- Remove unused indexes

### Write Performance

- More indexes = slower writes (INSERT, UPDATE, DELETE)
- Balance read vs write performance
- Consider partial indexes for large tables

### Index Selectivity

- High cardinality fields make good indexes
- Low cardinality fields (e.g., boolean) are poor standalone indexes

**Selectivity Examples**:

- ✅ Good: `email` (unique values)
- ✅ Good: `user_id` (many distinct values)
- ⚠️ OK in composite: `status` (few values, but helps narrow)
- ❌ Poor alone: `is_active` (only true/false)

---

## Verification Steps

After creating an index:

1. **Verify index exists**:

   ```sql
   SHOW INDEX FROM users;
   ```

2. **Verify index is used**:

   ```sql
   EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
   -- Look for key: idx_email
   ```

3. **Measure performance**:

   ```sql
   EXPLAIN ANALYZE SELECT * FROM users WHERE email = 'test@example.com';
   -- Check actual time
   ```

---

## Anti-Patterns

### ❌ Anti-Pattern 1: Too Many Indexes

**Problem**: 10+ indexes on one table

**Impact**: Slow writes, high storage usage

**Solution**: Remove unused indexes, consolidate similar indexes

```sql
-- Find unused indexes
SELECT * FROM sys.schema_unused_indexes;
```

---

### ❌ Anti-Pattern 2: Wrong Composite Index Order

**Problem**: Sort field before equality fields

```xml
<!-- ❌ BAD -->
<index name="idx_bad" columns="created_at,status"/>
```

**Solution**: Equality fields first

```xml
<!-- ✅ GOOD -->
<index name="idx_good" columns="status,created_at"/>
```

---

### ❌ Anti-Pattern 3: Indexing Low-Cardinality Fields Alone

**Problem**: Index on boolean or enum with few values

```xml
<!-- ❌ BAD: Only 2 possible values, poor selectivity -->
<index name="idx_is_active" columns="is_active"/>
```

**Solution**: Use composite index with more selective field first

```xml
<!-- ✅ GOOD: Combined with selective field -->
<index name="idx_active_created" columns="is_active,created_at"/>
```

---

### ❌ Anti-Pattern 4: Duplicate/Redundant Indexes

**Problem**: Multiple similar indexes

```xml
<!-- ❌ BAD: Second index is redundant -->
<index name="idx_status" columns="status"/>
<index name="idx_status_type" columns="status,type"/>
```

**Solution**: Keep only composite index (covers both cases due to leftmost prefix)

---

### ❌ Anti-Pattern 5: Functions on Indexed Columns

**Problem**: Using functions prevents index usage

```sql
-- ❌ BAD: Can't use index on email
SELECT * FROM users WHERE LOWER(email) = 'john@example.com';
```

**Solution**: Store normalized data or use generated column

---

## Index Creation Syntax

### Doctrine Migration

```php
public function up(Schema $schema): void
{
    // Simple index
    $this->addSql('CREATE INDEX idx_email ON users (email)');

    // Composite index
    $this->addSql('CREATE INDEX idx_status_created ON users (status, created_at)');

    // Unique index
    $this->addSql('CREATE UNIQUE INDEX uniq_email ON users (email)');

    // Online DDL (minimal locking)
    $this->addSql('CREATE INDEX idx_name ON users (name) ALGORITHM=INPLACE LOCK=NONE');
}
```

### XML Mapping

```xml
<entity name="App\User\Domain\Entity\User">
    <indexes>
        <index name="idx_email" columns="email"/>
        <index name="idx_status_created" columns="status,created_at"/>
    </indexes>
    <unique-constraints>
        <unique-constraint name="uniq_email" columns="email"/>
    </unique-constraints>
</entity>
```

---

## External Resources

- **[MySQL Index Documentation](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)**
- **[MariaDB Index Optimization](https://mariadb.com/kb/en/optimization-and-indexes/)**
- **[Use The Index, Luke](https://use-the-index-luke.com/)** - Excellent index tutorial
