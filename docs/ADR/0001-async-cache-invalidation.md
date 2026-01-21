# ADR 0001: Async Cache Invalidation for Domain Events

## Status

Accepted

## Context

When domain events are published (such as UserRegisteredEvent, UserUpdatedEvent, UserDeletedEvent), the system needs to invalidate related cache entries to maintain cache consistency. The question is whether this cache invalidation should happen synchronously (blocking the main request) or asynchronously (via message queue).

## Decision

We have decided to process cache invalidation event subscribers asynchronously via Symfony Messenger (AsyncSymfonyEventBus).

### Affected Subscribers

- `UserRegisteredCacheInvalidationSubscriber`
- `UserUpdatedCacheInvalidationSubscriber`
- `UserDeletedCacheInvalidationSubscriber`

### Implementation Details

- All cache invalidation subscribers run in Symfony Messenger workers
- Exceptions in subscribers propagate to `DomainEventMessageHandler`
- The handler catches exceptions, logs them, and emits failure metrics
- No retry mechanism is implemented by default

### CAP Theorem Trade-off

This decision follows the **AP** (Availability + Partition tolerance) approach over strong **Consistency**:

- **Availability**: Main requests complete immediately without waiting for cache invalidation
- **Partition tolerance**: Cache invalidation can continue even if the main service is temporarily degraded
- **Eventual consistency**: Cache may serve stale data briefly until async invalidation completes

## Consequences

### Positive

- Improved response time for user-facing requests
- Cache invalidation failures don't block main operations
- Better fault isolation between core operations and cache management
- Scalable cache invalidation via worker processes

### Negative

- Brief window of cache inconsistency (stale data may be served)
- Additional complexity in monitoring async processing
- Potential for data inconsistency if workers are down for extended periods

## Alternatives Considered

### Synchronous Cache Invalidation

- **Pros**: Immediate consistency, simpler mental model
- **Cons**: Slower response times, cache failures block main operations
- **Rejected because**: We prioritize availability and performance over immediate consistency

### Event Sourcing with Cache Rebuilding

- **Pros**: Complete cache rebuild from events, guaranteed consistency
- **Cons**: High complexity, significant architectural change
- **Rejected because**: Over-engineering for current scale and requirements

## References

- Implementation: `src/User/Application/EventSubscriber/*CacheInvalidationSubscriber.php`
- Event bus: `src/Shared/Infrastructure/Bus/Event/AsyncSymfonyEventBus.php`
- Message handler: `src/Shared/Infrastructure/Bus/Event/DomainEventMessageHandler.php`
- CAP Theorem: https://en.wikipedia.org/wiki/CAP_theorem
