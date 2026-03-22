# Database and Architecture Documentation

## Database Schema Changes

**Update if**: you add or modify tables, fields, indexes, or entity relationships.

**Update**: `docs/design-and-architecture.md`

1. Entity relationships
2. New fields and their purpose
3. Migration notes

**Example**:

```markdown
#### User Entity

- `id`: UUID (primary key)
- `email`: Unique, indexed
- `initials`: User initials
- `password`: Hashed password
- `confirmed`: Boolean confirmation status
```

**Update**: `docs/developer-guide.md` with repository usage patterns

## Domain Model Changes

**Update if**: you introduce new aggregates, commands, events, or change bounded context boundaries.

**Update**: `docs/design-and-architecture.md` (Domain design section)

1. **Aggregates**: New domain aggregates
2. **Commands**: Command handlers
3. **Events**: Domain events
4. **Bounded Contexts**: Context interactions

**Update**: `docs/glossary.md` with new domain terms

**Example**:

```markdown
## User Management Context

### Aggregates

- **User**: Root aggregate for user data

### Commands

- `RegisterUserCommand`: Register new user
- `UpdateUserCommand`: Update user details
- `ConfirmUserCommand`: Confirm user email

### Events

- `UserRegisteredEvent`: Emitted when user registered
- `UserConfirmedEvent`: Emitted when user confirmed
- `EmailChangedEvent`: Emitted when email changed
```

## Architecture Pattern Changes

**Update if**: you adopt new architectural patterns, refactor cross-cutting concerns, or deprecate existing approaches.

**Update**: `docs/design-and-architecture.md` (Architecture patterns section)

1. Pattern description
2. Implementation examples
3. Benefits and trade-offs
4. Migration path from old pattern

**Example**:

```markdown
### Domain-Driven Design with CQRS

#### Pattern Description

The User bounded context uses CQRS to separate read and write operations.

#### Implementation

- Commands are dispatched via Symfony Messenger
- Query handlers return DTOs for read operations
- Domain events are published for side effects

#### Benefits & Trade-offs

- **Benefits**: Clear separation of concerns, optimized read/write paths
- **Trade-offs**: Increased complexity, additional handler classes

#### Migration Path

1. Implement new command/query handlers
2. Update controllers to use new handlers
3. Remove direct repository calls from controllers
4. Update tests to use new patterns
```

## Documentation Standards

- Use Markdown headings (###/####) for sections and sub-sections
- Include code blocks with language hints and blank lines before/after fences
- Provide concise but complete examples covering purpose, implementation, benefits, and migration guidance
- Reference repository files with relative links so documentation stays navigable
- Always update `docs/glossary.md` when introducing new domain terms
