# Database and Architecture Documentation

## Database Schema Changes

**Update if**: you add or modify collections, fields, indexes, or entity relationships.

**Update**: [`AGENTS.md`](../../../../AGENTS.md) (Architecture overview section)

1. Entity relationships
1. New fields and their purpose
1. Migration notes

**Example**:

```markdown
#### Customer Entity

- `id`: ULID (primary key)
- `email`: Unique, indexed
- `type`: Reference to CustomerType (IRI)
- `status`: Reference to CustomerStatus (IRI)
```

**Update**: [`README.md`](../../../../README.md) with repository usage patterns

## Domain Model Changes

**Update if**: you introduce new aggregates, commands, events, or change bounded context boundaries.

**Update**: [`AGENTS.md`](../../../../AGENTS.md) (Domain design section)

1. **Aggregates**: New domain aggregates
1. **Commands**: Command handlers
1. **Events**: Domain events
1. **Bounded Contexts**: Context interactions

**Update**: [`README.md`](../../../../README.md) or team knowledge base with new domain terms

**Example**:

```markdown
## Customer Management Context

### Aggregates

- **Customer**: Root aggregate for customer data

### Commands

- `CreateCustomerCommand`: Create new customer
- `UpdateCustomerCommand`: Update customer details

### Events

- `CustomerCreatedEvent`: Emitted when customer created
- `CustomerUpdatedEvent`: Emitted when customer updated
```

## Architecture Pattern Changes

**Update if**: you adopt new architectural patterns, refactor cross-cutting concerns, or deprecate existing approaches.

**Update**: [`AGENTS.md`](../../../../AGENTS.md) (Architecture patterns section)

1. Pattern description
1. Implementation examples
1. Benefits and trade-offs
1. Migration path from old pattern

**Example**:

```markdown
### Domain-Driven Design with Event Sourcing

#### Pattern Description

Migrated the Customer bounded context from CRUD repositories to event-sourced aggregates to unlock audit trails and replay.

#### Implementation

- Customer aggregate publishes `CustomerCreatedEvent` and `CustomerUpdatedEvent`
- Repository coordinates between event store and projection layer
- Snapshot strategy reduces replay time for high-frequency aggregates

#### Benefits & Trade-offs

- **Benefits**: Full audit trail, temporal queries, event-driven integrations
- **Trade-offs**: Increased complexity, eventual consistency in projections, additional storage overhead

#### Migration Path

1. Implement event-sourced aggregate alongside existing CRUD repository
1. Dual-write to both systems during transition period
1. Backfill historical data as events
1. Switch read models to projections
1. Retire legacy CRUD repository once projections are stable
```

## Documentation Standards

- Use Markdown headings (###/####) for sections and sub-sections
- Include code blocks with language hints and blank lines before/after fences
- Provide concise but complete examples covering purpose, implementation, benefits, and migration guidance
- Reference repository files with relative links so documentation stays navigable
