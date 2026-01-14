# Cache Invalidation Strategies

Complete guide for implementing explicit cache invalidation: write-through, event-driven, tag-based, and time-based strategies.

## Core Principle: Explicit Over Implicit

**ALWAYS invalidate cache explicitly on write operations.** Never rely on TTL alone for data that changes via write commands.

```php
// ✅ CORRECT: Explicit invalidation
public function save(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();

    $this->cache->invalidateTags(["customer.{$customer->id()}"]);
}

// ❌ WRONG: Relying only on TTL
public function save(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();
    // Missing invalidation - stale data until TTL expires
}
```

---

## Invalidation Strategy Matrix

| Strategy          | When to Use                       | Complexity | Consistency |
| ----------------- | --------------------------------- | ---------- | ----------- |
| **Write-through** | Single entity CRUD operations     | Low        | Strong      |
| **Tag-based**     | Batch invalidation, related data  | Low        | Strong      |
| **Event-driven**  | Complex domain events, decoupling | Medium     | Strong      |
| **Time-based**    | Static data, aggregations         | Low        | Eventual    |
| **Manual**        | One-off operations, bulk imports  | Low        | User-driven |

---

## 1. Write-Through Invalidation

Invalidate immediately after the write.

**Use when**:

- Creating, updating, or deleting entities
- Strong consistency required

---

## 2. Tag-Based Invalidation

Use tags to invalidate multiple related cache entries.

**Common invalidation calls**:

```php
// Invalidate specific entity
$this->cache->invalidateTags(["customer.{$customerId}"]);

// Invalidate all customer caches (individual + lists)
$this->cache->invalidateTags(['customer']);

// Invalidate only list caches
$this->cache->invalidateTags(['customer.list']);
```

---

## 3. Event-Driven Invalidation

Invalidate cache in response to domain events.

**Why**:

- Decouples cache from business logic
- Easy to add side effects without touching core flow
- More testable invalidation rules

**Pattern**:

- Command handler persists changes
- Domain events emitted
- Subscribers invalidate cache (best-effort)

---

## 4. Time-Based Invalidation (TTL Only)

Use only when staleness is acceptable or data changes outside your control.

---

## 5. Manual Invalidation

Provide a console command or admin-only endpoint for bulk/operational invalidation.

---

## Anti-Patterns

- Clearing *all* cache without reason (`$cache->clear()`)
- Over-invalidation (`invalidateTags(['customer'])` for single update)
- Invalidating inside repositories' `save()` when you already have domain events

---

## Summary

- ✅ Invalidate explicitly on create/update/delete
- ✅ Prefer tag-based + event-driven invalidation
- ✅ Always wrap invalidation in try/catch in subscribers (best-effort)
- ✅ Test stale reads after writes
