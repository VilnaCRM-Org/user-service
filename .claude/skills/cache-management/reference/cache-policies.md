# Cache Policies Reference

Complete guide for declaring cache policies: cache keys, TTLs, consistency classes, and invalidation strategies.

## Cache Policy Declaration Template

Before implementing caching, declare the complete policy:

```php
/**
 * Cache Policy for {Operation/Query}
 *
 * Key Pattern: {namespace}.{identifier}
 * TTL: {duration} ({reason})
 * Consistency: {Strong|Eventual|SWR}
 * Invalidation: {trigger conditions}
 * Tags: [{tag1}, {tag2}]
 * Notes: {additional considerations}
 */
```

**Example**:

```php
/**
 * Cache Policy for Customer By ID Query
 *
 * Key Pattern: customer.{id}
 * TTL: 300s (5 minutes - balance between freshness and performance)
 * Consistency: Stale-While-Revalidate
 * Invalidation: On customer update/delete commands
 * Tags: [customer, customer.{id}]
 * Notes: Read-heavy operation, tolerates brief staleness
 */
public function findById(string $id): ?Customer
{
    // Implementation...
}
```

---

## Cache Key Design

### Key Naming Pattern

**Format**: `{namespace}.{entity}.{identifier}.{variation}`

**Components**:

- **Namespace**: Domain/module (e.g., `customer`, `order`, `product`)
- **Entity**: Specific entity type (optional if namespace is entity)
- **Identifier**: Unique ID, filter, or query parameter
- **Variation**: Optional version, locale, or variant

**Examples**:

```php
// Single entity by ID
'customer.{id}' => 'customer.abc123'

// List queries
'customer.list.active' => 'customer.list.active'
'customer.list.page.{page}' => 'customer.list.page.1'

// Filtered queries
'order.by_customer.{customerId}' => 'order.by_customer.abc123'
'product.category.{categoryId}.active' => 'product.category.electronics.active'

// Aggregations
'stats.customer.count.{date}' => 'stats.customer.count.2024-12-10'
'metrics.revenue.daily.{date}' => 'metrics.revenue.daily.2024-12-10'

// Versioned keys (when cache structure changes)
'customer.v2.{id}' => 'customer.v2.abc123'
```

### Key Design Best Practices

**✅ DO**:

- Use lowercase with dots/underscores
- Include namespace to avoid collisions
- Keep keys short but descriptive
- Use consistent patterns across codebase
- Include version if structure might change
- Make keys predictable and debuggable

**❌ DON'T**:

- Use special characters (!, @, #, $, %, etc.)
- Include sensitive data in keys
- Create extremely long keys (>100 chars)
- Use dynamic/unpredictable patterns
- Mix naming conventions

---

## TTL Selection Guide

### TTL Decision Matrix

| Data Freshness Requirement | TTL Range     | Use Cases                          |
| -------------------------- | ------------- | ---------------------------------- |
| **Real-time**              | No cache      | Live notifications, stock prices   |
| **Near real-time**         | 1-10 seconds  | Live dashboards, active sessions   |
| **Fresh**                  | 30-60 seconds | Search results, recommendations    |
| **Moderately fresh**       | 5-15 minutes  | User profiles, product details     |
| **Stable**                 | 1-6 hours     | Product catalogs, category lists   |
| **Static**                 | 1-7 days      | Configuration, rarely-changed data |

### TTL Calculation Factors

**Consider these factors when choosing TTL**:

1. **Data change frequency**

   - Frequently updated → Shorter TTL
   - Rarely updated → Longer TTL

2. **Business impact of stale data**

   - High impact (prices, inventory) → Shorter TTL or invalidation
   - Low impact (descriptions, images) → Longer TTL

3. **Query cost**

   - Expensive queries → Longer TTL with invalidation
   - Cheap queries → Shorter TTL or no cache

4. **Traffic patterns**
   - High traffic → Longer TTL to reduce load
   - Low traffic → Shorter TTL acceptable

### TTL Examples by Entity Type

```php
// User profile (updated occasionally)
$item->expiresAfter(600); // 10 minutes

// Product catalog (updated rarely)
$item->expiresAfter(3600); // 1 hour

// Search results (can be slightly stale)
$item->expiresAfter(60); // 1 minute

// Configuration (very stable)
$item->expiresAfter(86400); // 24 hours

// Session data (security-sensitive)
$item->expiresAfter(1800); // 30 minutes

// Aggregated statistics (computed overnight)
$item->expiresAfter(43200); // 12 hours
```

---

## Consistency Classes

### 1. Strong Consistency (No Cache)

**When to use**:

- Real-time data required
- Security-sensitive operations
- Financial transactions
- Inventory management

### 2. Eventual Consistency

**When to use**:

- Read-heavy operations
- Tolerate brief staleness
- Non-critical data

### 3. Stale-While-Revalidate (SWR)

**When to use**:

- High traffic queries
- Tolerate stale data briefly
- Want fast responses + fresh data

**See**: `swr-pattern.md` for complete SWR implementation guidance.

---

## Cache Tags for Invalidation

### Tag Design Patterns

**Use hierarchical tags for flexible invalidation**:

```php
// Single entity - multiple tags
$item->tag([
    'customer',           // Invalidate ALL customers
    "customer.{$id}",     // Invalidate SPECIFIC customer
]);

// List queries - broader tags
$item->tag([
    'customer',           // All customer data
    'customer.list',      // All customer lists
    'customer.list.active', // Specific list variant
]);
```

---

## Summary

Every cached query must have a declared policy:

- ✅ Explicit cache key pattern
- ✅ Defined TTL with rationale
- ✅ Declared consistency class
- ✅ Documented invalidation strategy
- ✅ Configured cache tags
