# Stale-While-Revalidate (SWR) Pattern

Guide for implementing stale-while-revalidate caching for high-traffic, read-heavy queries that tolerate brief staleness.

## Quick rule

For most cases in Symfony, use `TagAwareCacheInterface::get()` with the `beta` parameter (probabilistic early expiration).

```php
return $this->cache->get(
    $cacheKey,
    function (ItemInterface $item) {
        $item->expiresAfter(300);
        $item->tag(['customer', 'customer.123']);

        return $this->loadFromDatabase();
    },
    beta: 1.0
);
```

## When to use SWR

- High traffic endpoints
- Expensive queries
- Data tolerates brief staleness

## When NOT to use SWR

- Financial/critical data
- Security-sensitive reads
- Real-time requirements

## Notes

- SWR improves tail latency by reducing “cache stampede” when TTL expires.
- Combine SWR for reads with explicit invalidation for writes.
